<?php

namespace App\Services;

/**
 * Сервис для управления правами доступа
 * 
 * Обеспечивает проверку прав доступа пользователей,
 * управление списками отделов и пользователей с доступом
 * Документация: https://context7.com/bitrix24/rest/
 */
class AccessControlService
{
    protected ConfigService $configService;
    protected Bitrix24ApiService $apiService;
    protected UserService $userService;
    protected LoggerService $logger;
    
    public function __construct(
        ConfigService $configService,
        Bitrix24ApiService $apiService,
        UserService $userService,
        LoggerService $logger
    ) {
        $this->configService = $configService;
        $this->apiService = $apiService;
        $this->userService = $userService;
        $this->logger = $logger;
    }
    
    /**
     * Проверка прав доступа пользователя
     * 
     * @param int $userId ID пользователя
     * @param array $userDepartments Массив ID отделов пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return bool true если доступ разрешён
     */
    public function checkUserAccess(int $userId, array $userDepartments, string $authId, string $domain): bool
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            // Получаем данные пользователя для проверки статуса администратора
            $userStartTime = microtime(true);
            $user = $this->userService->getCurrentUser($authId, $domain);
            $userTime = microtime(true) - $userStartTime;
            
            if (!$user) {
                // Не удалось получить данные пользователя — запрещаем доступ
                $this->logger->logAccessCheck($userId, $userDepartments, 'denied', 'user_data_error', [
                    'performance' => [
                        'total_time' => microtime(true) - $startTime,
                        'user_fetch_time' => $userTime,
                        'memory_used' => memory_get_usage() - $startMemory
                    ]
                ]);
                return false;
            }
            
            // Проверка статуса администратора
            $adminCheckStartTime = microtime(true);
            if ($this->userService->isAdmin($user, $authId, $domain)) {
                // Администраторы всегда имеют доступ
                $adminCheckTime = microtime(true) - $adminCheckStartTime;
                $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'admin', [
                    'performance' => [
                        'total_time' => microtime(true) - $startTime,
                        'user_fetch_time' => $userTime,
                        'admin_check_time' => $adminCheckTime,
                        'memory_used' => memory_get_usage() - $startMemory
                    ]
                ]);
                return true;
            }
            $adminCheckTime = microtime(true) - $adminCheckStartTime;
            
            // Получаем конфигурацию прав доступа
            $configStartTime = microtime(true);
            $accessConfig = $this->configService->getAccessConfig();
            $configTime = microtime(true) - $configStartTime;
            
            // Если проверка отключена — доступ разрешён
            if (!isset($accessConfig['access_control']['enabled']) || !$accessConfig['access_control']['enabled']) {
                $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'check_disabled', [
                    'performance' => [
                        'total_time' => microtime(true) - $startTime,
                        'config_fetch_time' => $configTime,
                        'memory_used' => memory_get_usage() - $startMemory
                    ]
                ]);
                return true;
            }
            
            $departments = $accessConfig['access_control']['departments'] ?? [];
            $users = $accessConfig['access_control']['users'] ?? [];
            
            // Если списки пустые — доступ запрещён
            if (empty($departments) && empty($users)) {
                $this->logger->logAccessCheck($userId, $userDepartments, 'denied', 'no_access_rules', [
                    'performance' => [
                        'total_time' => microtime(true) - $startTime,
                        'config_fetch_time' => $configTime,
                        'memory_used' => memory_get_usage() - $startMemory
                    ]
                ]);
                return false;
            }
            
            // Оптимизация: создаём массив ID отделов для быстрого поиска O(1)
            $deptIndexStartTime = microtime(true);
            $departmentIds = [];
            if (!empty($departments) && is_array($departments)) {
                foreach ($departments as $dept) {
                    if (isset($dept['id'])) {
                        $departmentIds[(int)$dept['id']] = true;
                    }
                }
            }
            $deptIndexTime = microtime(true) - $deptIndexStartTime;
            
            // Проверка отделов - O(1) для каждого отдела
            $deptCheckStartTime = microtime(true);
            if (!empty($userDepartments) && is_array($userDepartments)) {
                foreach ($userDepartments as $deptId) {
                    if (isset($departmentIds[(int)$deptId])) {
                        $deptCheckTime = microtime(true) - $deptCheckStartTime;
                        $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'department_in_list', [
                            'performance' => [
                                'total_time' => microtime(true) - $startTime,
                                'config_fetch_time' => $configTime,
                                'department_index_time' => $deptIndexTime,
                                'department_check_time' => $deptCheckTime,
                                'departments_count' => count($departments),
                                'user_departments_count' => count($userDepartments),
                                'memory_used' => memory_get_usage() - $startMemory
                            ]
                        ]);
                        return true;
                    }
                }
            }
            $deptCheckTime = microtime(true) - $deptCheckStartTime;
            
            // Оптимизация: создаём массив ID пользователей для быстрого поиска O(1)
            $userIndexStartTime = microtime(true);
            $userIds = [];
            if (!empty($users) && is_array($users)) {
                foreach ($users as $user) {
                    if (isset($user['id'])) {
                        $userIds[(int)$user['id']] = true;
                    }
                }
            }
            $userIndexTime = microtime(true) - $userIndexStartTime;
            
            // Проверка пользователей - O(1)
            $userCheckStartTime = microtime(true);
            if (isset($userIds[$userId])) {
                $userCheckTime = microtime(true) - $userCheckStartTime;
                $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'user_in_list', [
                    'performance' => [
                        'total_time' => microtime(true) - $startTime,
                        'config_fetch_time' => $configTime,
                        'user_index_time' => $userIndexTime,
                        'user_check_time' => $userCheckTime,
                        'users_count' => count($users),
                        'memory_used' => memory_get_usage() - $startMemory
                    ]
                ]);
                return true;
            }
            $userCheckTime = microtime(true) - $userCheckStartTime;
            
            // Доступ запрещён
            $this->logger->logAccessCheck($userId, $userDepartments, 'denied', 'not_in_lists', [
                'performance' => [
                    'total_time' => microtime(true) - $startTime,
                    'config_fetch_time' => $configTime,
                    'department_index_time' => $deptIndexTime,
                    'department_check_time' => $deptCheckTime,
                    'user_index_time' => $userIndexTime,
                    'user_check_time' => $userCheckTime,
                    'departments_count' => count($departments),
                    'users_count' => count($users),
                    'memory_used' => memory_get_usage() - $startMemory
                ]
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->logAccessCheck($userId, $userDepartments, 'denied', 'exception', [
                'error' => $e->getMessage(),
                'performance' => [
                    'total_time' => microtime(true) - $startTime,
                    'memory_used' => memory_get_usage() - $startMemory
                ]
            ]);
            return false;
        }
    }
    
    /**
     * Добавление отдела в список доступа
     * 
     * @param int $departmentId ID отдела
     * @param string $departmentName Название отдела
     * @param array $addedBy Информация о том, кто добавил ['id' => int, 'name' => string]
     * @return array Результат операции ['success' => bool, 'error' => string|null]
     */
    public function addDepartment(int $departmentId, string $departmentName, array $addedBy): array
    {
        // Валидация входных данных
        if (empty($departmentId) || $departmentId <= 0) {
            return ['success' => false, 'error' => 'Не указан ID отдела'];
        }
        
        if (empty($departmentName)) {
            return ['success' => false, 'error' => 'Не указано название отдела'];
        }
        
        $config = $this->configService->getAccessConfig();
        
        // Инициализируем массив отделов, если его нет
        if (!isset($config['access_control']['departments']) || !is_array($config['access_control']['departments'])) {
            $config['access_control']['departments'] = [];
        }
        
        // Проверяем, нет ли уже такого отдела
        foreach ($config['access_control']['departments'] as $dept) {
            if (isset($dept['id']) && (int)$dept['id'] == (int)$departmentId) {
                return ['success' => false, 'error' => 'Отдел уже есть в списке доступа'];
            }
        }
        
        $config['access_control']['departments'][] = [
            'id' => (int)$departmentId,
            'name' => trim($departmentName),
            'added_at' => date('Y-m-d H:i:s'),
            'added_by' => $addedBy
        ];
        
        $config['access_control']['last_updated'] = date('Y-m-d H:i:s');
        $config['access_control']['updated_by'] = $addedBy;
        
        $saveResult = $this->configService->saveAccessConfig($config);
        if ($saveResult['success']) {
            $this->logger->logAccessControl('add_department', [
                'department_id' => $departmentId,
                'department_name' => $departmentName,
                'added_by' => $addedBy
            ]);
            return ['success' => true, 'error' => null];
        } else {
            return ['success' => false, 'error' => $saveResult['error'] ?? 'Ошибка при сохранении конфигурации'];
        }
    }
    
    /**
     * Удаление отдела из списка доступа
     * 
     * @param int $departmentId ID отдела
     * @return bool true если успешно
     */
    public function removeDepartment(int $departmentId): bool
    {
        $config = $this->configService->getAccessConfig();
        
        $departments = $config['access_control']['departments'] ?? [];
        $newDepartments = [];
        
        foreach ($departments as $dept) {
            if (isset($dept['id']) && $dept['id'] != $departmentId) {
                $newDepartments[] = $dept;
            }
        }
        
        $config['access_control']['departments'] = $newDepartments;
        $config['access_control']['last_updated'] = date('Y-m-d H:i:s');
        
        $saveResult = $this->configService->saveAccessConfig($config);
        if ($saveResult['success']) {
            $this->logger->logAccessControl('remove_department', [
                'department_id' => $departmentId
            ]);
        }
        return $saveResult['success'];
    }
    
    /**
     * Добавление пользователя в список доступа
     * 
     * @param int $userId ID пользователя
     * @param string $userName ФИО пользователя
     * @param string|null $userEmail Email пользователя
     * @param array $addedBy Информация о том, кто добавил ['id' => int, 'name' => string]
     * @return array Результат операции ['success' => bool, 'error' => string|null]
     */
    public function addUser(int $userId, string $userName, ?string $userEmail, array $addedBy): array
    {
        // Валидация входных данных
        if (empty($userId) || $userId <= 0) {
            return ['success' => false, 'error' => 'Не указан ID пользователя'];
        }
        
        if (empty($userName)) {
            return ['success' => false, 'error' => 'Не указано имя пользователя'];
        }
        
        $config = $this->configService->getAccessConfig();
        
        // Инициализируем массив пользователей, если его нет
        if (!isset($config['access_control']['users']) || !is_array($config['access_control']['users'])) {
            $config['access_control']['users'] = [];
        }
        
        // Проверяем, нет ли уже такого пользователя
        foreach ($config['access_control']['users'] as $user) {
            if (isset($user['id']) && (int)$user['id'] == (int)$userId) {
                return ['success' => false, 'error' => 'Пользователь уже есть в списке доступа'];
            }
        }
        
        $config['access_control']['users'][] = [
            'id' => (int)$userId,
            'name' => trim($userName),
            'email' => $userEmail ? trim($userEmail) : null,
            'added_at' => date('Y-m-d H:i:s'),
            'added_by' => $addedBy
        ];
        
        $config['access_control']['last_updated'] = date('Y-m-d H:i:s');
        $config['access_control']['updated_by'] = $addedBy;
        
        $saveResult = $this->configService->saveAccessConfig($config);
        if ($saveResult['success']) {
            $this->logger->logAccessControl('add_user', [
                'user_id' => $userId,
                'user_name' => $userName,
                'added_by' => $addedBy
            ]);
            return ['success' => true, 'error' => null];
        } else {
            return ['success' => false, 'error' => $saveResult['error'] ?? 'Ошибка при сохранении конфигурации'];
        }
    }
    
    /**
     * Удаление пользователя из списка доступа
     * 
     * @param int $userId ID пользователя
     * @return bool true если успешно
     */
    public function removeUser(int $userId): bool
    {
        $config = $this->configService->getAccessConfig();
        
        $users = $config['access_control']['users'] ?? [];
        $newUsers = [];
        
        foreach ($users as $user) {
            if (isset($user['id']) && $user['id'] != $userId) {
                $newUsers[] = $user;
            }
        }
        
        $config['access_control']['users'] = $newUsers;
        $config['access_control']['last_updated'] = date('Y-m-d H:i:s');
        
        $saveResult = $this->configService->saveAccessConfig($config);
        if ($saveResult['success']) {
            $this->logger->logAccessControl('remove_user', [
                'user_id' => $userId
            ]);
        }
        return $saveResult['success'];
    }
    
    /**
     * Включение/выключение проверки прав доступа
     * 
     * @param bool $enabled Включить или выключить проверку
     * @param array $updatedBy Информация о том, кто обновил ['id' => int, 'name' => string]
     * @return array Результат операции ['success' => bool, 'error' => string|null]
     */
    public function toggleAccessControl(bool $enabled, array $updatedBy): array
    {
        $config = $this->configService->getAccessConfig();
        
        $config['access_control']['enabled'] = $enabled;
        $config['access_control']['last_updated'] = date('Y-m-d H:i:s');
        $config['access_control']['updated_by'] = $updatedBy;
        
        $saveResult = $this->configService->saveAccessConfig($config);
        if ($saveResult['success']) {
            $this->logger->logAccessControl('toggle', [
                'enabled' => $enabled,
                'updated_by' => $updatedBy
            ]);
            return ['success' => true, 'error' => null];
        } else {
            return ['success' => false, 'error' => $saveResult['error'] ?? 'Ошибка при сохранении конфигурации'];
        }
    }
    
    /**
     * Массовое добавление отделов в список доступа
     * 
     * @param array $departments Массив отделов [['id' => int, 'name' => string], ...]
     * @param array $addedBy Информация о том, кто добавил ['id' => int, 'name' => string]
     * @return array Результат операции ['success' => bool, 'error' => string|null, 'added' => int, 'skipped' => int]
     */
    public function addDepartments(array $departments, array $addedBy): array
    {
        // Валидация входных данных
        if (empty($departments) || !is_array($departments)) {
            return ['success' => false, 'error' => 'Не указаны отделы для добавления', 'added' => 0, 'skipped' => 0];
        }
        
        $config = $this->configService->getAccessConfig();
        
        if (!isset($config['access_control']['departments']) || !is_array($config['access_control']['departments'])) {
            $config['access_control']['departments'] = [];
        }
        
        // Создаём индекс существующих отделов для быстрой проверки
        $existingIds = [];
        foreach ($config['access_control']['departments'] as $dept) {
            if (isset($dept['id'])) {
                $existingIds[(int)$dept['id']] = true;
            }
        }
        
        $added = 0;
        $skipped = 0;
        
        foreach ($departments as $dept) {
            // Валидация структуры отдела
            if (!isset($dept['id']) || !isset($dept['name'])) {
                $skipped++;
                continue;
            }
            
            $deptId = (int)$dept['id'];
            $deptName = trim($dept['name']);
            
            // Валидация значений
            if ($deptId <= 0 || empty($deptName)) {
                $skipped++;
                continue;
            }
            
            // Проверка на дубликаты
            if (isset($existingIds[$deptId])) {
                $skipped++;
                continue;
            }
            
            // Добавление отдела
            $config['access_control']['departments'][] = [
                'id' => $deptId,
                'name' => $deptName,
                'added_at' => date('Y-m-d H:i:s'),
                'added_by' => $addedBy
            ];
            
            $existingIds[$deptId] = true;
            $added++;
        }
        
        if ($added === 0) {
            return ['success' => false, 'error' => 'Не удалось добавить отделы (все уже существуют или невалидны)', 'added' => 0, 'skipped' => $skipped];
        }
        
        $config['access_control']['last_updated'] = date('Y-m-d H:i:s');
        $config['access_control']['updated_by'] = $addedBy;
        
        $saveResult = $this->configService->saveAccessConfig($config);
        if ($saveResult['success']) {
            $this->logger->logAccessControl('add_departments_bulk', [
                'added' => $added,
                'skipped' => $skipped,
                'added_by' => $addedBy
            ]);
            return ['success' => true, 'error' => null, 'added' => $added, 'skipped' => $skipped];
        } else {
            return ['success' => false, 'error' => $saveResult['error'] ?? 'Ошибка при сохранении конфигурации', 'added' => 0, 'skipped' => $skipped];
        }
    }
    
    /**
     * Массовое добавление пользователей в список доступа
     * 
     * @param array $users Массив пользователей [['id' => int, 'name' => string, 'email' => string|null], ...]
     * @param array $addedBy Информация о том, кто добавил ['id' => int, 'name' => string]
     * @return array Результат операции ['success' => bool, 'error' => string|null, 'added' => int, 'skipped' => int]
     */
    public function addUsers(array $users, array $addedBy): array
    {
        // Валидация входных данных
        if (empty($users) || !is_array($users)) {
            return ['success' => false, 'error' => 'Не указаны пользователи для добавления', 'added' => 0, 'skipped' => 0];
        }
        
        $config = $this->configService->getAccessConfig();
        
        if (!isset($config['access_control']['users']) || !is_array($config['access_control']['users'])) {
            $config['access_control']['users'] = [];
        }
        
        // Создаём индекс существующих пользователей для быстрой проверки
        $existingIds = [];
        foreach ($config['access_control']['users'] as $user) {
            if (isset($user['id'])) {
                $existingIds[(int)$user['id']] = true;
            }
        }
        
        $added = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            // Валидация структуры пользователя
            if (!isset($user['id']) || !isset($user['name'])) {
                $skipped++;
                continue;
            }
            
            $userId = (int)$user['id'];
            $userName = trim($user['name']);
            
            // Валидация значений
            if ($userId <= 0 || empty($userName)) {
                $skipped++;
                continue;
            }
            
            // Проверка на дубликаты
            if (isset($existingIds[$userId])) {
                $skipped++;
                continue;
            }
            
            // Добавление пользователя
            $config['access_control']['users'][] = [
                'id' => $userId,
                'name' => $userName,
                'email' => isset($user['email']) ? trim($user['email']) : null,
                'added_at' => date('Y-m-d H:i:s'),
                'added_by' => $addedBy
            ];
            
            $existingIds[$userId] = true;
            $added++;
        }
        
        if ($added === 0) {
            return ['success' => false, 'error' => 'Не удалось добавить пользователей (все уже существуют или невалидны)', 'added' => 0, 'skipped' => $skipped];
        }
        
        $config['access_control']['last_updated'] = date('Y-m-d H:i:s');
        $config['access_control']['updated_by'] = $addedBy;
        
        $saveResult = $this->configService->saveAccessConfig($config);
        if ($saveResult['success']) {
            $this->logger->logAccessControl('add_users_bulk', [
                'added' => $added,
                'skipped' => $skipped,
                'added_by' => $addedBy
            ]);
            return ['success' => true, 'error' => null, 'added' => $added, 'skipped' => $skipped];
        } else {
            return ['success' => false, 'error' => $saveResult['error'] ?? 'Ошибка при сохранении конфигурации', 'added' => 0, 'skipped' => $skipped];
        }
    }
}







