<?php

namespace App\Clients;

require_once(__DIR__ . '/../../crest.php');

use App\Services\LoggerService;
use App\Exceptions\Bitrix24ApiException;

/**
 * Клиент для работы с Bitrix24 REST API
 * 
 * Обертка над библиотекой CRest с унифицированной обработкой ошибок
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24Client implements ApiClientInterface
{
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Вызов метода Bitrix24 REST API
     * 
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ от API
     * @throws Bitrix24ApiException При ошибке API
     */
    public function call(string $method, array $params = []): array
    {
        $startTime = microtime(true);
        
        $this->logger->log('Bitrix24 API call', [
            'method' => $method,
            'params' => $this->sanitizeParams($params)
        ], 'info');
        
        try {
            $result = \CRest::call($method, $params);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if (isset($result['error'])) {
                $this->handleApiError($result, $method);
            }
            
            $this->logger->log('Bitrix24 API success', [
                'method' => $method,
                'execution_time_ms' => $executionTime,
                'response_size' => strlen(json_encode($result))
            ], 'info');
            
            return $result;
        } catch (Bitrix24ApiException $e) {
            // Пробрасываем исключение дальше
            throw $e;
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->logError('Bitrix24 API exception', [
                'method' => $method,
                'exception' => $e->getMessage(),
                'execution_time_ms' => $executionTime
            ]);
            
            throw new Bitrix24ApiException(
                "API call failed: {$method}",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Батч-запросы к Bitrix24 REST API
     * 
     * @param array $commands Массив команд для выполнения
     * @param int $halt Остановка при ошибке (0 или 1)
     * @return array Ответ от API
     * @throws Bitrix24ApiException При ошибке API
     */
    public function callBatch(array $commands, int $halt = 0): array
    {
        $startTime = microtime(true);
        
        $this->logger->log('Bitrix24 API batch call', [
            'commands_count' => count($commands),
            'halt' => $halt
        ], 'info');
        
        try {
            $result = \CRest::callBatch($commands, $halt);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if (isset($result['error'])) {
                $this->handleApiError($result, 'batch');
            }
            
            $this->logger->log('Bitrix24 API batch success', [
                'commands_count' => count($commands),
                'execution_time_ms' => $executionTime
            ], 'info');
            
            return $result;
        } catch (Bitrix24ApiException $e) {
            // Пробрасываем исключение дальше
            throw $e;
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->logError('Bitrix24 API batch exception', [
                'exception' => $e->getMessage(),
                'execution_time_ms' => $executionTime
            ]);
            
            throw new Bitrix24ApiException(
                "API batch call failed",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Обновление токена авторизации
     * 
     * @return bool true если токен обновлен успешно
     */
    public function refreshToken(): bool
    {
        try {
            // CRest автоматически обновляет токен при ошибке expired_token
            // Этот метод можно использовать для принудительного обновления
            $settings = $this->getSettings();
            
            if (empty($settings['refresh_token'])) {
                return false;
            }
            
            // Логика обновления токена через GetNewAuth в CRest
            // CRest делает это автоматически при expired_token
            return true;
        } catch (\Exception $e) {
            $this->logger->logError('Token refresh failed', [
                'exception' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Получение настроек приложения
     * 
     * @return array Настройки приложения
     */
    public function getSettings(): array
    {
        try {
            // Используем рефлексию для доступа к защищенному методу CRest
            // или создаем публичный метод в CRest
            // Пока возвращаем пустой массив, так как getAppSettings() защищен
            return [];
        } catch (\Exception $e) {
            $this->logger->logError('Failed to get settings', [
                'exception' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Обработка ошибок API
     * 
     * @param array $result Результат запроса с ошибкой
     * @param string $method Метод API
     * @throws Bitrix24ApiException
     */
    protected function handleApiError(array $result, string $method): void
    {
        $error = $result['error'] ?? 'unknown_error';
        $errorDescription = $result['error_description'] ?? $result['error_information'] ?? 'No description';
        
        $this->logger->logError('Bitrix24 API error', [
            'method' => $method,
            'error' => $error,
            'error_description' => $errorDescription
        ]);
        
        // Маппинг ошибок API на исключения
        $errorMap = [
            'expired_token' => 'Токен истек, требуется обновление',
            'invalid_token' => 'Невалидный токен, требуется переустановка приложения',
            'invalid_grant' => 'Ошибка авторизации, проверьте настройки',
            'QUERY_LIMIT_EXCEEDED' => 'Превышен лимит запросов, попробуйте позже',
            'ERROR_METHOD_NOT_FOUND' => 'Метод API не найден',
            'NO_AUTH_FOUND' => 'Ошибка авторизации в Bitrix24',
            'INTERNAL_SERVER_ERROR' => 'Внутренняя ошибка сервера Bitrix24',
            'no_install_app' => 'Приложение не установлено, требуется установка',
            'error_php_lib_curl' => 'Ошибка PHP библиотеки cURL'
        ];
        
        $message = $errorMap[$error] ?? $errorDescription;
        
        throw new Bitrix24ApiException(
            "API error: {$message}",
            0,
            null,
            $error,
            $errorDescription
        );
    }
    
    /**
     * Очистка параметров для логирования (удаление секретов)
     * 
     * @param array $params Параметры запроса
     * @return array Очищенные параметры
     */
    protected function sanitizeParams(array $params): array
    {
        $sanitized = $params;
        
        // Удаляем секретные данные из логов
        if (isset($sanitized['auth'])) {
            $authValue = $sanitized['auth'];
            if (is_string($authValue) && strlen($authValue) > 10) {
                $sanitized['auth'] = substr($authValue, 0, 10) . '...';
            }
        }
        
        // Удаляем другие потенциально секретные поля
        $secretFields = ['password', 'secret', 'token', 'key', 'access_token', 'refresh_token'];
        foreach ($secretFields as $field) {
            if (isset($sanitized[$field])) {
                $value = $sanitized[$field];
                if (is_string($value) && strlen($value) > 10) {
                    $sanitized[$field] = substr($value, 0, 10) . '...';
                }
            }
        }
        
        return $sanitized;
    }
}

