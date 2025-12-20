<?php

namespace App\Services;

require_once(__DIR__ . '/../../crest.php');

/**
 * Сервис для работы с Bitrix24 REST API
 * 
 * Обеспечивает единый интерфейс для всех вызовов Bitrix24 REST API
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24ApiService
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
     */
    public function call(string $method, array $params = []): array
    {
        $result = CRest::call($method, $params);
        
        if (isset($result['error'])) {
            $this->logger->logError('Bitrix24 API error', [
                'method' => $method,
                'error' => $result['error'],
                'error_description' => $result['error_description'] ?? null
            ]);
        }
        
        return $result;
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
        
        $url = 'https://' . $domain . '/rest/user.current.json';
        $requestParams = ['auth' => $authId];
        $params = http_build_query($requestParams);
        
        // Логирование запроса для отладки
        $requestLog = [
            'url' => $url,
            'params' => $params,
            'auth_length' => strlen($authId),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $this->logger->log('User.current API request', $requestLog, 'info');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $responsePreview = substr($response, 0, 500);
        curl_close($ch);
        
        // Логирование ответа
        $responseLog = [
            'http_code' => $httpCode,
            'curl_error' => $curlError ?: 'none',
            'response_preview' => $responsePreview,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $this->logger->log('User.current API response', $responseLog, 'info');
        
        if ($curlError) {
            return ['error' => 'curl_error', 'error_description' => $curlError];
        }
        
        if ($httpCode !== 200) {
            $result = json_decode($response, true);
            if (isset($result['error'])) {
                return $result;
            }
            return ['error' => 'http_error', 'error_description' => 'HTTP Code: ' . $httpCode . '. Response: ' . $responsePreview];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'json_error', 'error_description' => json_last_error_msg()];
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
        
        $url = 'https://' . $domain . '/rest/department.get.json';
        $params = http_build_query([
            'auth' => $authId,
            'ID' => $departmentId
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Логирование для отладки
        $deptApiLog = [
            'department_id' => $departmentId,
            'url' => $url,
            'http_code' => $httpCode,
            'curl_error' => $curlError ?: 'none',
            'response_preview' => substr($response, 0, 500),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $this->logger->log('Department.get API call', $deptApiLog, 'info');
        
        if ($curlError) {
            return null;
        }
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        // Проверяем наличие ошибки в ответе
        if (isset($result['error'])) {
            $deptErrorLog = [
                'department_id' => $departmentId,
                'error' => $result['error'],
                'error_description' => $result['error_description'] ?? 'no description',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $this->logger->logError('Department.get API error', $deptErrorLog);
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
        
        $url = 'https://' . $domain . '/rest/department.get.json';
        $params = http_build_query(['auth' => $authId]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $result = null;
        
        if ($curlError || $httpCode !== 200) {
            // Пробуем через CRest (токен установщика)
            $result = CRest::call('department.get', []);
        } else {
            $result = json_decode($response, true);
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
        
        $url = 'https://' . $domain . '/rest/user.get.json';
        
        $requestParams = ['auth' => $authId];
        
        // Если есть поисковый запрос — добавляем фильтр
        if ($search) {
            $requestParams['filter'] = [
                'NAME' => '%' . $search . '%',
                'EMAIL' => '%' . $search . '%'
            ];
        }
        
        $params = http_build_query($requestParams);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $result = null;
        
        if ($curlError || $httpCode !== 200) {
            // Пробуем через CRest (токен установщика)
            $result = CRest::call('user.get', $search ? ['filter' => $requestParams['filter']] : []);
        } else {
            $result = json_decode($response, true);
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
    }
    
    /**
     * Проверка статуса администратора
     * 
     * Метод: user.admin
     * Документация: https://context7.com/bitrix24/rest/user.admin
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return bool true если администратор
     */
    public function checkIsAdmin(string $authId, string $domain): bool
    {
        if (empty($authId) || empty($domain)) {
            return false;
        }
        
        $url = 'https://' . $domain . '/rest/user.admin.json';
        $params = http_build_query(['auth' => $authId]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['result'])) {
                return ($result['result'] === true || $result['result'] === 'true' || $result['result'] == 1);
            }
        }
        
        // Fallback: через CRest
        $adminCheckResult = CRest::call('user.admin', []);
        if (isset($adminCheckResult['result'])) {
            return ($adminCheckResult['result'] === true || $adminCheckResult['result'] === 'true' || $adminCheckResult['result'] == 1);
        }
        
        return false;
    }
}

