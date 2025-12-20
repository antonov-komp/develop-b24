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
            // Инициализируем клиент с токеном пользователя
            $this->client->initializeWithUserToken($authId, $domain);
            
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
                $this->logger->logError('User.current API returned error', [
                    'error' => $result['error'],
                    'error_description' => $result['error_description'] ?? null
                ]);
                return null;
            }
            
            // Проверка наличия результата
            if (!isset($result['result'])) {
                $this->logger->logError('User.current API returned no result', [
                    'result_keys' => array_keys($result)
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
            $this->logger->logError('User.current API error (SDK)', [
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription(),
                'message' => $e->getMessage()
            ]);
            return null;
        } catch (\Exception $e) {
            $this->logger->logError('User.current API exception (SDK)', [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
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
                try {
                    $this->client->initializeWithInstallerToken();
                    $result = $this->client->call('department.get', []);
                } catch (Bitrix24ApiException $e) {
                    $this->logger->logError('Department.get API error (fallback)', [
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
                try {
                    $this->client->initializeWithInstallerToken();
                    $result = $this->client->call('user.get', $params);
                } catch (Bitrix24ApiException $e) {
                    $this->logger->logError('User.get API error (fallback)', [
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
            try {
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
                $this->logger->logError('User.admin API error (fallback)', [
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

