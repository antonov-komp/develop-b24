<?php

namespace App\Clients;

use Bitrix24\SDK\Core\CoreBuilder;
use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\AuthToken;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\WebhookUrl;
use Bitrix24\SDK\Core\Credentials\Endpoints;
use Bitrix24\SDK\Core\Credentials\Scope;
use Bitrix24\SDK\Core\Exceptions\BaseException;
use App\Services\LoggerService;
use App\Exceptions\Bitrix24ApiException;

/**
 * Клиент для работы с Bitrix24 REST API через официальный SDK (b24phpsdk)
 * 
 * Реализация клиента для работы с Bitrix24 REST API через b24phpsdk
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24SdkClient implements ApiClientInterface
{
    protected LoggerService $logger;
    protected ?Core $core = null;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Инициализация с токеном установщика
     * 
     * Читает настройки из settings.json и создает Core с токеном установщика
     * 
     * @param string|null $accessToken Токен доступа (если не указан, читается из settings.json)
     * @param string|null $domain Домен портала (если не указан, читается из settings.json)
     * @throws Bitrix24ApiException При ошибке инициализации
     */
    public function initializeWithInstallerToken(?string $accessToken = null, ?string $domain = null): void
    {
        $settings = $this->getSettings();
        
        $token = $accessToken ?? $settings['access_token'] ?? null;
        $portalDomain = $domain ?? $settings['domain'] ?? null;
        $refreshToken = $settings['refresh_token'] ?? null;
        $expiresIn = isset($settings['expires_in']) ? (int)$settings['expires_in'] : 3600;
        $clientEndpoint = $settings['client_endpoint'] ?? null;
        
        // Валидация
        if (!$token || !$portalDomain) {
            throw new Bitrix24ApiException('Access token or domain not found in settings.json');
        }
        
        // Очистка домена
        $portalDomain = preg_replace('#^https?://#', '', $portalDomain);
        $portalDomain = rtrim($portalDomain, '/');
        
        try {
            // Создание AuthToken
            $expires = time() + $expiresIn;
            $authToken = new AuthToken(
                $token,
                $refreshToken,
                $expires
            );
            
            // Создание Endpoints
            $clientUrl = $clientEndpoint ?? "https://{$portalDomain}";
            // Явно указываем authServerUrl для избежания проблем с валидацией
            $authServerUrl = 'https://oauth.bitrix.info/'; // West region по умолчанию
            $endpoints = new Endpoints($clientUrl, $authServerUrl);
            
            // Создание ApplicationProfile
            // Для токена установщика используем application_token как client_id, если доступен
            // Иначе используем минимальные значения (SDK требует ApplicationProfile для OAuth токенов)
            $clientId = $settings['client_id'] ?? $settings['application_token'] ?? '';
            $clientSecret = $settings['client_secret'] ?? '';
            $scope = $settings['scope'] ?? 'crm';
            
            // Если нет client_id и application_token, используем минимальные значения
            // Это может вызвать ошибку "wrong_client", но SDK требует ApplicationProfile
            if (empty($clientId)) {
                $clientId = 'minimal';
            }
            if (empty($clientSecret)) {
                // Используем application_token как client_secret, если доступен
                $clientSecret = $settings['application_token'] ?? 'minimal';
            }
            
            $applicationProfile = new ApplicationProfile(
                $clientId,
                $clientSecret,
                Scope::initFromString($scope)
            );
            
            // Создание Credentials
            $credentials = new Credentials(
                null, // webhookUrl
                $authToken,
                $applicationProfile,
                $endpoints
            );
            
            // Создание Core через CoreBuilder
            $coreBuilder = new CoreBuilder();
            $coreBuilder->withCredentials($credentials);
            
            // Используем наш LoggerService через адаптер (если нужно)
            // Пока используем NullLogger по умолчанию
            $this->core = $coreBuilder->build();
            
            $this->logger->log('SDK client initialized with installer token', [
                'domain' => $portalDomain
            ], 'info');
            
        } catch (\Exception $e) {
            $this->logger->logError('Failed to initialize SDK client', [
                'exception' => $e->getMessage(),
                'domain' => $portalDomain
            ]);
            
            throw new Bitrix24ApiException("Failed to initialize: {$e->getMessage()}", 0, $e);
        }
    }
    
    /**
     * Инициализация с токеном пользователя
     * 
     * @param string $authId ID авторизации пользователя
     * @param string $domain Домен портала
     * @throws Bitrix24ApiException При ошибке инициализации
     */
    public function initializeWithUserToken(string $authId, string $domain): void
    {
        // Валидация
        if (empty($authId) || empty($domain)) {
            throw new Bitrix24ApiException('Auth ID and domain are required');
        }
        
        // Очистка домена
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        
        try {
            // Создание AuthToken (для пользовательского токена нет refresh_token)
            $authToken = new AuthToken(
                $authId,
                null, // refresh_token
                time() + 3600 // expires
            );
            
            // Создание Endpoints
            // Явно указываем authServerUrl для избежания проблем с валидацией
            $authServerUrl = 'https://oauth.bitrix.info/'; // West region по умолчанию
            $endpoints = new Endpoints("https://{$domain}", $authServerUrl);
            
            // Создание ApplicationProfile (минимальный)
            $applicationProfile = new ApplicationProfile(
                'user',
                'user',
                Scope::initFromString('crm')
            );
            
            // Создание Credentials
            $credentials = new Credentials(
                null, // webhookUrl
                $authToken,
                $applicationProfile,
                $endpoints
            );
            
            // Создание Core через CoreBuilder
            $coreBuilder = new CoreBuilder();
            $coreBuilder->withCredentials($credentials);
            $this->core = $coreBuilder->build();
            
            $this->logger->log('SDK client initialized with user token', [
                'domain' => $domain
            ], 'info');
            
        } catch (\Exception $e) {
            $this->logger->logError('Failed to initialize with user token', [
                'exception' => $e->getMessage(),
                'domain' => $domain
            ]);
            
            throw new Bitrix24ApiException("Failed to initialize: {$e->getMessage()}", 0, $e);
        }
    }
    
    /**
     * Инициализация с вебхуком
     * 
     * @param string $webhookUrl URL вебхука
     * @throws Bitrix24ApiException При ошибке инициализации
     */
    public function initializeWithWebhook(string $webhookUrl): void
    {
        // Валидация
        if (empty($webhookUrl)) {
            throw new Bitrix24ApiException('Webhook URL is required');
        }
        
        // Проверка формата URL
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            throw new Bitrix24ApiException('Invalid webhook URL format');
        }
        
        // Проверка Bitrix24 URL
        if (strpos($webhookUrl, 'bitrix24') === false && strpos($webhookUrl, '/rest/') === false) {
            $this->logger->log('Warning: Webhook URL does not look like Bitrix24 URL', [
                'url' => substr($webhookUrl, 0, 50) . '...'
            ], 'warning');
        }
        
        try {
            // Создание WebhookUrl
            $webhook = new WebhookUrl($webhookUrl);
            
            // Создание Credentials
            $credentials = new Credentials(
                $webhook,
                null, // authToken
                null, // applicationProfile
                null  // endpoints
            );
            
            // Создание Core через CoreBuilder
            $coreBuilder = new CoreBuilder();
            $coreBuilder->withCredentials($credentials);
            $this->core = $coreBuilder->build();
            
            $this->logger->log('SDK client initialized with webhook', [
                'url' => substr($webhookUrl, 0, 50) . '...'
            ], 'info');
            
        } catch (\Exception $e) {
            $this->logger->logError('Failed to initialize with webhook', [
                'exception' => $e->getMessage()
            ]);
            
            throw new Bitrix24ApiException("Failed to initialize: {$e->getMessage()}", 0, $e);
        }
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
        if (!$this->core) {
            $this->initializeWithInstallerToken();
        }
        
        $startTime = microtime(true);
        
        // Логирование запроса
        $this->logger->log('Bitrix24 API call (SDK)', [
            'method' => $method,
            'params' => $this->sanitizeParams($params)
        ], 'info');
        
        try {
            // Вызов через SDK
            $response = $this->core->call($method, $params);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Преобразование ответа SDK в стандартный формат
            $responseData = $response->getResponseData();
            $result = [
                'result' => $responseData->getResult(),
            ];
            
            // Добавляем total и next, если есть
            $pagination = $responseData->getPagination();
            if ($pagination) {
                if ($pagination->getTotal() !== null) {
                    $result['total'] = $pagination->getTotal();
                }
                if ($pagination->getNextItem() !== null) {
                    $result['next'] = $pagination->getNextItem();
                }
            }
            
            // Логирование успеха
            $this->logger->log('Bitrix24 API success (SDK)', [
                'method' => $method,
                'execution_time_ms' => $executionTime
            ], 'info');
            
            return $result;
        } catch (BaseException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->logError('Bitrix24 API exception (SDK)', [
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
        if (!$this->core) {
            $this->initializeWithInstallerToken();
        }
        
        $startTime = microtime(true);
        
        $this->logger->log('Bitrix24 API batch call (SDK)', [
            'commands_count' => count($commands),
            'halt' => $halt
        ], 'info');
        
        try {
            // Преобразуем команды в формат Bitrix24 batch API
            // Формат: cmd[key] = method(params)
            $batchCommands = [];
            foreach ($commands as $key => $command) {
                $method = $command['method'];
                $params = $command['params'] ?? [];
                
                // Формируем строку команды: method?param1=value1&param2=value2
                if (!empty($params)) {
                    $queryString = http_build_query($params);
                    $batchCommands[$key] = "{$method}?{$queryString}";
                } else {
                    $batchCommands[$key] = $method;
                }
            }
            
            // Вызов batch через SDK
            // Bitrix24 batch API использует метод 'batch' с параметром cmd
            $response = $this->core->call('batch', [
                'cmd' => $batchCommands,
                'halt' => $halt
            ]);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Получение результата из ответа SDK
            $responseData = $response->getResponseData();
            $result = $responseData->getResult();
            
            // Обработка ошибок в batch (если halt = 0)
            if (!$halt && isset($result['result_error'])) {
                $this->logger->log('Bitrix24 API batch partial errors', [
                    'errors' => $result['result_error']
                ], 'warning');
            }
            
            $this->logger->log('Bitrix24 API batch success (SDK)', [
                'commands_count' => count($commands),
                'execution_time_ms' => $executionTime
            ], 'info');
            
            return $result;
        } catch (BaseException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->logError('Bitrix24 API batch exception (SDK)', [
                'commands_count' => count($commands),
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
     * Получение настроек приложения
     * 
     * @return array Настройки приложения
     */
    protected function getSettings(): array
    {
        try {
            $settingsFile = __DIR__ . '/../../settings.json';
            
            if (!file_exists($settingsFile)) {
                return [];
            }
            
            $content = file_get_contents($settingsFile);
            if ($content === false) {
                return [];
            }
            
            $settings = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->logError('Failed to parse settings.json', [
                    'error' => json_last_error_msg()
                ]);
                return [];
            }
            
            return $settings;
        } catch (\Exception $e) {
            $this->logger->logError('Failed to get settings', [
                'exception' => $e->getMessage()
            ]);
            
            return [];
        }
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

