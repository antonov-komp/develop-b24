<?php

namespace App\Services;

use App\Clients\Bitrix24SdkClient;
use App\Exceptions\Bitrix24ApiException;

/**
 * Сервис для работы с Bitrix24 REST API
 * 
 * Обеспечивает единый интерфейс для всех вызовов Bitrix24 REST API
 * Использует Bitrix24SdkClient (b24phpsdk) вместо CRest
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24ApiService
{
    protected Bitrix24SdkClient $client;
    protected LoggerService $logger;
    
    public function __construct(Bitrix24SdkClient $client, LoggerService $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }
    
    /**
     * Вызов метода Bitrix24 REST API
     * 
     * Использует Bitrix24SdkClient для токена установщика
     * 
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ от API
     */
    public function call(string $method, array $params = []): array
    {
        try {
            return $this->client->call($method, $params);
        } catch (Bitrix24ApiException $e) {
            $this->logger->logError('Bitrix24 API error in service', [
                'method' => $method,
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription()
            ]);
            
            // Возвращаем результат с ошибкой для обратной совместимости
            return [
                'error' => $e->getApiError() ?? 'unknown_error',
                'error_description' => $e->getApiErrorDescription() ?? $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение текущих прав доступа (scope) приложения
     * 
     * Метод: scope
     * Документация: https://context7.com/bitrix24/rest/scope
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Текущие права доступа или null при ошибке
     */
    public function getCurrentScope(string $authId, string $domain): ?array
    {
        try {
            $this->client->initializeWithUserToken($authId, $domain);
            $result = $this->client->call('scope', []);
            
            if (isset($result['error'])) {
                $this->logger->logError('Failed to get current scope', [
                    'error' => $result['error'],
                    'error_description' => $result['error_description'] ?? null
                ]);
                return null;
            }
            
            return $result['result'] ?? null;
        } catch (\Exception $e) {
            $this->logger->logError('Exception getting current scope', [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Получение всех возможных прав доступа
     * 
     * Метод: scope (с параметром full=true)
     * Документация: https://context7.com/bitrix24/rest/scope
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Все возможные права или null при ошибке
     */
    public function getAllAvailableScope(string $authId, string $domain): ?array
    {
        try {
            $this->client->initializeWithUserToken($authId, $domain);
            $result = $this->client->call('scope', ['full' => true]);
            
            if (isset($result['error'])) {
                $this->logger->logError('Failed to get all available scope', [
                    'error' => $result['error'],
                    'error_description' => $result['error_description'] ?? null
                ]);
                return null;
            }
            
            return $result['result'] ?? null;
        } catch (\Exception $e) {
            $this->logger->logError('Exception getting all available scope', [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Проверка доступности метода API
     * 
     * Метод: method.get
     * Документация: https://context7.com/bitrix24/rest/method.get
     * 
     * @param string $methodName Название метода для проверки
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Информация о доступности метода или null при ошибке
     */
    public function checkMethodAvailability(string $methodName, string $authId, string $domain): ?array
    {
        try {
            $this->client->initializeWithUserToken($authId, $domain);
            $result = $this->client->call('method.get', ['name' => $methodName]);
            
            if (isset($result['error'])) {
                return [
                    'method' => $methodName,
                    'is_existing' => false,
                    'is_available' => false,
                    'error' => $result['error']
                ];
            }
            
            return $result['result'] ?? null;
        } catch (\Exception $e) {
            $this->logger->logError('Exception checking method availability', [
                'method' => $methodName,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Получение списка доступных методов API
     * 
     * Метод: methods
     * Документация: https://context7.com/bitrix24/rest/methods
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Список доступных методов или null при ошибке
     */
    public function getAvailableMethods(string $authId, string $domain): ?array
    {
        try {
            $this->client->initializeWithUserToken($authId, $domain);
            $result = $this->client->call('methods', []);
            
            if (isset($result['error'])) {
                $this->logger->logError('Failed to get available methods', [
                    'error' => $result['error'],
                    'error_description' => $result['error_description'] ?? null
                ]);
                return null;
            }
            
            return $result['result'] ?? null;
        } catch (\Exception $e) {
            $this->logger->logError('Exception getting available methods', [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Проверка доступности нескольких методов API
     * 
     * @param array $methods Массив названий методов для проверки
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array Результаты проверки для каждого метода
     */
    public function checkMultipleMethodsAvailability(array $methods, string $authId, string $domain): array
    {
        $results = [];
        
        foreach ($methods as $method) {
            $checkResult = $this->checkMethodAvailability($method, $authId, $domain);
            $results[$method] = [
                'is_existing' => $checkResult['is_existing'] ?? false,
                'is_available' => $checkResult['is_available'] ?? false,
                'error' => $checkResult['error'] ?? null
            ];
        }
        
        return $results;
    }
    
    /**
     * Получение текущего пользователя
     * 
     * Метод: user.current
     * Документация: https://context7.com/bitrix24/rest/user.current
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Данные пользователя или null при ошибке
     */
    public function getCurrentUser(string $authId, string $domain): ?array
    {
        if (empty($authId) || empty($domain)) {
            return null;
        }
        
        // Логирование запроса для отладки
        $this->logger->log('User.current API request (SDK)', [
            'domain' => $domain,
            'auth_length' => strlen($authId),
            'timestamp' => date('Y-m-d H:i:s')
        ], 'info');
        
        try {
            // Получаем дополнительные параметры из запроса (если есть)
            $refreshId = $_REQUEST['REFRESH_ID'] ?? null;
            $authExpires = isset($_REQUEST['AUTH_EXPIRES']) ? (int)$_REQUEST['AUTH_EXPIRES'] : null;
            
            // Логирование параметров для диагностики
            $this->logger->log('Initializing SDK client with user token', [
                'auth_id_length' => strlen($authId),
                'has_refresh_id' => !empty($refreshId),
                'auth_expires' => $authExpires,
                'domain' => $domain
            ], 'info');
            
            // Инициализируем клиент с токеном пользователя
            $this->client->initializeWithUserToken($authId, $domain, $refreshId, $authExpires);
            
            // Вызываем через SDK
            $result = $this->client->call('user.current', []);
            
            // Логирование ответа
            $this->logger->log('User.current API response (SDK)', [
                'has_result' => isset($result['result']),
                'has_error' => isset($result['error']),
                'result_type' => isset($result['result']) ? gettype($result['result']) : 'null',
                'result_is_array' => isset($result['result']) && is_array($result['result']),
                'result_keys' => isset($result['result']) && is_array($result['result']) ? array_keys($result['result']) : [],
                'timestamp' => date('Y-m-d H:i:s')
            ], 'info');
            
            // Проверка на ошибку
            if (isset($result['error'])) {
                $errorCode = $result['error'] ?? 'UNKNOWN';
                $errorDescription = $result['error_description'] ?? 'No description';
                
                $this->logger->logError('User.current API returned error (SDK)', [
                    'error' => $errorCode,
                    'error_description' => $errorDescription,
                    'domain' => $domain,
                    'auth_id_length' => strlen($authId)
                ]);
                
                // Логируем детальную информацию об ошибке
                if ($errorCode === 'INVALID_TOKEN' || $errorCode === 'expired_token') {
                    $this->logger->logError('User.current API: Token is invalid or expired', [
                        'error_code' => $errorCode,
                        'domain' => $domain,
                        'suggestion' => 'Check if AUTH_ID token is valid and not expired'
                    ]);
                } elseif ($errorCode === 'NO_AUTH_FOUND') {
                    $this->logger->logError('User.current API: No authentication found', [
                        'error_code' => $errorCode,
                        'domain' => $domain,
                        'suggestion' => 'Check if token matches the domain and is properly configured'
                    ]);
                }
                
                return null;
            }
            
            // Проверка наличия результата
            if (!isset($result['result'])) {
                $this->logger->logError('User.current API returned no result', [
                    'result_keys' => array_keys($result),
                    'full_result' => $result,
                    'domain' => $domain,
                    'auth_id_length' => strlen($authId)
                ]);
                return null;
            }
            
            $userData = $result['result'];
            
            // Проверка типа: должен быть массив
            if (!is_array($userData)) {
                $this->logger->logError('User.current API returned non-array result', [
                    'result_type' => gettype($userData),
                    'result' => $userData
                ]);
                return null;
            }
            
            // Проверка на пустой массив (это тоже ошибка для user.current)
            if (empty($userData)) {
                $this->logger->logError('User.current API returned empty array', []);
                return null;
            }
            
            // Проверка наличия ID пользователя (обязательное поле)
            if (!isset($userData['ID']) || empty($userData['ID'])) {
                $this->logger->logError('User.current API returned user without ID', [
                    'result_keys' => array_keys($userData)
                ]);
                return null;
            }
            
            return $userData;
        } catch (Bitrix24ApiException $e) {
            // Проверяем, является ли ошибка критической (network, timeout)
            $isCriticalError = $this->isCriticalError($e);
            
            $this->logger->logError('User.current API error (SDK)', [
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription(),
                'message' => $e->getMessage(),
                'domain' => $domain,
                'auth_id_length' => strlen($authId),
                'is_critical' => $isCriticalError
            ]);
            
            // Fallback только для критических ошибок (network, timeout)
            if ($isCriticalError) {
                $this->logger->log('Trying direct REST API call as fallback for critical error', [
                    'domain' => $domain,
                    'auth_id_length' => strlen($authId),
                    'error_type' => $e->getApiError()
                ], 'warning');
                
                $directResult = $this->callDirectRestApi('user.current', $authId, $domain);
                if ($directResult !== null && isset($directResult['result'])) {
                    $this->logger->log('Direct REST API call successful (fallback)', [
                        'has_result' => isset($directResult['result']),
                        'domain' => $domain
                    ], 'info');
                    return $directResult['result'];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            // Проверяем, является ли ошибка критической (network, timeout)
            $isCriticalError = $this->isCriticalNetworkError($e);
            
            $this->logger->logError('User.current API exception (SDK)', [
                'exception' => $e->getMessage(),
                'exception_class' => get_class($e),
                'domain' => $domain,
                'auth_id_length' => strlen($authId),
                'is_critical' => $isCriticalError
            ]);
            
            // Fallback только для критических ошибок (network, timeout)
            if ($isCriticalError) {
                $this->logger->log('Trying direct REST API call as fallback for critical error', [
                    'domain' => $domain,
                    'auth_id_length' => strlen($authId),
                    'exception_type' => get_class($e)
                ], 'warning');
                
                $directResult = $this->callDirectRestApi('user.current', $authId, $domain);
                if ($directResult !== null && isset($directResult['result'])) {
                    $this->logger->log('Direct REST API call successful (fallback)', [
                        'has_result' => isset($directResult['result']),
                        'domain' => $domain
                    ], 'info');
                    return $directResult['result'];
                }
            }
            
            return null;
        }
    }
    
    /**
     * Проверка, является ли ошибка критической (network, timeout)
     * 
     * @param Bitrix24ApiException $e Исключение
     * @return bool true если ошибка критическая
     */
    private function isCriticalError(Bitrix24ApiException $e): bool
    {
        $errorCode = $e->getApiError() ?? '';
        $message = strtolower($e->getMessage());
        
        // Критические ошибки: network, timeout, transport
        $criticalPatterns = [
            'transport',
            'timeout',
            'network',
            'connection',
            'dns',
            'curl'
        ];
        
        foreach ($criticalPatterns as $pattern) {
            if (stripos($errorCode, $pattern) !== false || stripos($message, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Проверка, является ли исключение критической ошибкой сети
     * 
     * @param \Exception $e Исключение
     * @return bool true если ошибка критическая
     */
    private function isCriticalNetworkError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        $className = strtolower(get_class($e));
        
        // Критические ошибки: network, timeout, transport
        $criticalPatterns = [
            'transport',
            'timeout',
            'network',
            'connection',
            'dns',
            'curl'
        ];
        
        foreach ($criticalPatterns as $pattern) {
            if (stripos($message, $pattern) !== false || stripos($className, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Прямой вызов Bitrix24 REST API через HTTP
     * 
     * Используется как fallback только для критических ошибок (network, timeout)
     * 
     * @param string $method Метод API
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Ответ от API или null при ошибке
     */
    private function callDirectRestApi(string $method, string $authId, string $domain): ?array
    {
        // Очистка домена
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        
        $url = "https://{$domain}/rest/{$method}.json";
        $params = ['auth' => $authId];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $this->logger->logError('Direct REST API call failed (curl error)', [
                'method' => $method,
                'curl_error' => $curlError,
                'domain' => $domain
            ]);
            return null;
        }
        
        if ($httpCode !== 200) {
            $this->logger->logError('Direct REST API call failed (HTTP error)', [
                'method' => $method,
                'http_code' => $httpCode,
                'domain' => $domain
            ]);
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->logError('Direct REST API call failed (JSON error)', [
                'method' => $method,
                'json_error' => json_last_error_msg(),
                'domain' => $domain
            ]);
            return null;
        }
        
        if (isset($result['error'])) {
            $this->logger->logError('Direct REST API call returned error', [
                'method' => $method,
                'error' => $result['error'] ?? 'unknown',
                'error_description' => $result['error_description'] ?? 'no description',
                'domain' => $domain
            ]);
            return null;
        }
        
        return $result;
    }
    
    /**
     * Получение пользователя по ID
     * 
     * Метод: user.get
     * Документация: https://context7.com/bitrix24/rest/user.get
     * 
     * @param int $userId ID пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Данные пользователя или null при ошибке
     */
    public function getUser(int $userId, string $authId, string $domain): ?array
    {
        if (empty($userId) || empty($authId) || empty($domain)) {
            return null;
        }
        
        $result = $this->call('user.get', ['ID' => $userId]);
        
        if (isset($result['error']) || !isset($result['result'])) {
            return null;
        }
        
        $users = $result['result'];
        
        // Обработка структуры ответа
        if (is_array($users)) {
            foreach ($users as $user) {
                if (isset($user['ID']) && (int)$user['ID'] === $userId) {
                    return $user;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Получение отдела по ID
     * 
     * Метод: department.get
     * Документация: https://context7.com/bitrix24/rest/department.get
     * 
     * @param int $departmentId ID отдела
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Данные отдела или null при ошибке
     */
    public function getDepartment(int $departmentId, string $authId, string $domain): ?array
    {
        if (empty($departmentId) || empty($authId) || empty($domain)) {
            return null;
        }
        
        // Логирование для отладки
        $this->logger->log('Department.get API call (SDK)', [
            'department_id' => $departmentId,
            'domain' => $domain,
            'timestamp' => date('Y-m-d H:i:s')
        ], 'info');
        
        try {
            // Инициализируем клиент с токеном пользователя
            $this->client->initializeWithUserToken($authId, $domain);
            
            // Вызываем через SDK
            $result = $this->client->call('department.get', ['ID' => $departmentId]);
            
            // Проверяем наличие ошибки в ответе
            if (isset($result['error'])) {
                $this->logger->logError('Department.get API error (SDK)', [
                    'department_id' => $departmentId,
                    'error' => $result['error'],
                    'error_description' => $result['error_description'] ?? 'no description'
                ]);
                return null;
            }
            
            // Метод department.get возвращает массив отделов в result
            if (isset($result['result']) && is_array($result['result'])) {
                // Если result - массив отделов
                if (isset($result['result'][0]) && is_array($result['result'][0])) {
                    return $result['result'][0];
                }
                // Если result - один отдел (не массив)
                if (isset($result['result']['ID']) || isset($result['result']['NAME'])) {
                    return $result['result'];
                }
            }
            
            return null;
        } catch (Bitrix24ApiException $e) {
            $this->logger->logError('Department.get API exception (SDK)', [
                'department_id' => $departmentId,
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription()
            ]);
            return null;
        } catch (\Exception $e) {
            $this->logger->logError('Department.get API exception (SDK)', [
                'department_id' => $departmentId,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Получение всех отделов
     * 
     * Метод: department.get (без параметра ID)
     * Документация: https://context7.com/bitrix24/rest/department.get
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array Массив отделов [['id' => 1, 'name' => 'Отдел продаж'], ...]
     */
    public function getAllDepartments(string $authId, string $domain): array
    {
        if (empty($authId) || empty($domain)) {
            return [];
        }
        
        try {
            // Инициализируем клиент с токеном пользователя
            $this->client->initializeWithUserToken($authId, $domain);
            
            // Вызываем через SDK
            $result = $this->client->call('department.get', []);
            
            if (isset($result['error']) || !isset($result['result'])) {
                // Fallback: пробуем через токен установщика
                // Это не критическая ошибка сети, а альтернативный способ авторизации
                // Токен установщика может иметь права, которых нет у токена пользователя
                try {
                    $this->logger->log('Trying department.get with installer token (fallback)', [
                        'reason' => 'User token failed, trying installer token'
                    ], 'info');
                    
                    $this->client->initializeWithInstallerToken();
                    $result = $this->client->call('department.get', []);
                } catch (Bitrix24ApiException $e) {
                    $this->logger->logError('Department.get API error (fallback to installer token failed)', [
                        'error' => $e->getApiError(),
                        'error_description' => $e->getApiErrorDescription()
                    ]);
                    return [];
                }
            }
            
            if (isset($result['error']) || !isset($result['result'])) {
                return [];
            }
            
            $departments = [];
            $resultData = $result['result'];
            
            // Обработка разных вариантов структуры ответа
            if (is_array($resultData)) {
                foreach ($resultData as $dept) {
                    if (is_array($dept) && isset($dept['ID'])) {
                        $departments[] = [
                            'id' => (int)$dept['ID'],
                            'name' => $dept['NAME'] ?? 'Без названия'
                        ];
                    }
                }
            }
            
            return $departments;
        } catch (Bitrix24ApiException $e) {
            $this->logger->logError('Department.get API exception (SDK)', [
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->logError('Department.get API exception (SDK)', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Получение всех пользователей
     * 
     * Метод: user.get (без параметра ID)
     * Документация: https://context7.com/bitrix24/rest/user.get
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @param string|null $search Поисковый запрос (имя или email)
     * @return array Массив пользователей [['id' => 1, 'name' => 'Иван Иванов', 'email' => 'ivan@example.com'], ...]
     */
    public function getAllUsers(string $authId, string $domain, ?string $search = null): array
    {
        if (empty($authId) || empty($domain)) {
            return [];
        }
        
        $params = [];
        
        // Если есть поисковый запрос — добавляем фильтр
        if ($search) {
            $params['filter'] = [
                'NAME' => '%' . $search . '%',
                'EMAIL' => '%' . $search . '%'
            ];
        }
        
        try {
            // Инициализируем клиент с токеном пользователя
            $this->client->initializeWithUserToken($authId, $domain);
            
            // Вызываем через SDK
            $result = $this->client->call('user.get', $params);
            
            if (isset($result['error']) || !isset($result['result'])) {
                // Fallback: пробуем через токен установщика
                // Это не критическая ошибка сети, а альтернативный способ авторизации
                // Токен установщика может иметь права, которых нет у токена пользователя
                try {
                    $this->logger->log('Trying user.get with installer token (fallback)', [
                        'reason' => 'User token failed, trying installer token'
                    ], 'info');
                    
                    $this->client->initializeWithInstallerToken();
                    $result = $this->client->call('user.get', $params);
                } catch (Bitrix24ApiException $e) {
                    $this->logger->logError('User.get API error (fallback to installer token failed)', [
                        'error' => $e->getApiError(),
                        'error_description' => $e->getApiErrorDescription()
                    ]);
                    return [];
                }
            }
            
            if (isset($result['error']) || !isset($result['result'])) {
                return [];
            }
            
            $users = [];
            $resultData = $result['result'];
            
            // Обработка структуры ответа
            if (is_array($resultData)) {
                foreach ($resultData as $user) {
                    if (is_array($user) && isset($user['ID'])) {
                        $users[] = [
                            'id' => (int)$user['ID'],
                            'name' => trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? '')),
                            'email' => $user['EMAIL'] ?? null
                        ];
                    }
                }
            }
            
            return $users;
        } catch (Bitrix24ApiException $e) {
            $this->logger->logError('User.get API exception (SDK)', [
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logger->logError('User.get API exception (SDK)', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Проверка статуса администратора
     * 
     * Метод: user.admin
     * Документация: https://context7.com/bitrix24/rest/user.admin
     * 
     * @param string $authId Токен авторизации (если пустой, используется токен установщика)
     * @param string $domain Домен портала (если пустой, используется из настроек)
     * @return bool true если администратор
     */
    public function checkIsAdmin(string $authId, string $domain): bool
    {
        // Если токен пустой, используем токен установщика через call()
        if (empty($authId)) {
            try {
                $adminCheckResult = $this->call('user.admin', []);
                if (isset($adminCheckResult['result'])) {
                    $resultValue = $adminCheckResult['result'];
                    // Если result - это массив, берем первый элемент
                    if (is_array($resultValue) && !empty($resultValue)) {
                        $resultValue = $resultValue[0];
                    }
                    return ($resultValue === true || $resultValue === 'true' || $resultValue == 1 || $resultValue === 1);
                }
            } catch (\Exception $e) {
                $this->logger->logError('Error checking admin status with installer token (SDK)', [
                    'exception' => $e->getMessage()
                ]);
            }
            return false;
        }
        
        if (empty($domain)) {
            return false;
        }
        
        try {
            // Инициализируем клиент с токеном пользователя
            $this->client->initializeWithUserToken($authId, $domain);
            
            // Вызываем через SDK
            $result = $this->client->call('user.admin', []);
            
            // Логирование результата для отладки
            $this->logger->log('User.admin API response (SDK)', [
                'has_result' => isset($result['result']),
                'result_type' => isset($result['result']) ? gettype($result['result']) : 'null',
                'result_value' => isset($result['result']) ? var_export($result['result'], true) : 'null',
                'result_raw' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'info');
            
            if (isset($result['result'])) {
                // Метод user.admin возвращает массив [true] или [false], а не просто true/false
                $resultValue = $result['result'];
                
                // Если result - это массив, берем первый элемент
                if (is_array($resultValue) && !empty($resultValue)) {
                    $resultValue = $resultValue[0];
                }
                
                // Проверяем значение
                $isAdmin = (
                    $resultValue === true || 
                    $resultValue === 'true' || 
                    $resultValue == 1 || 
                    $resultValue === 1 ||
                    $resultValue === 'Y' || 
                    $resultValue === 'y' ||
                    $resultValue === 'yes' ||
                    $resultValue === 'YES'
                );
                
                $this->logger->log('User.admin check result', [
                    'is_admin' => $isAdmin,
                    'result_value' => var_export($resultValue, true),
                    'result_type' => gettype($resultValue),
                    'original_result' => var_export($result['result'], true)
                ], 'info');
                
                return $isAdmin;
            } else {
                $this->logger->logError('User.admin API returned no result', [
                    'result_keys' => array_keys($result),
                    'full_result' => $result
                ]);
            }
            
            // Fallback: через токен установщика
            // Это не критическая ошибка сети, а альтернативный способ авторизации
            // Токен установщика может иметь права, которых нет у токена пользователя
            try {
                $this->logger->log('Trying user.admin with installer token (fallback)', [
                    'reason' => 'User token failed, trying installer token'
                ], 'info');
                
                $this->client->initializeWithInstallerToken();
                $adminCheckResult = $this->client->call('user.admin', []);
                if (isset($adminCheckResult['result'])) {
                    $resultValue = $adminCheckResult['result'];
                    // Если result - это массив, берем первый элемент
                    if (is_array($resultValue) && !empty($resultValue)) {
                        $resultValue = $resultValue[0];
                    }
                    return ($resultValue === true || $resultValue === 'true' || $resultValue == 1 || $resultValue === 1);
                }
            } catch (Bitrix24ApiException $e) {
                $this->logger->logError('User.admin API error (fallback to installer token failed)', [
                    'error' => $e->getApiError(),
                    'error_description' => $e->getApiErrorDescription()
                ]);
            }
        } catch (Bitrix24ApiException $e) {
            $this->logger->logError('User.admin API exception (SDK)', [
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription()
            ]);
        } catch (\Exception $e) {
            $this->logger->logError('User.admin API exception (SDK)', [
                'exception' => $e->getMessage()
            ]);
        }
        
        return false;
    }
}

