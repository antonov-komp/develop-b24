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
        // Получаем данные пользователя для проверки статуса администратора
        $user = $this->userService->getCurrentUser($authId, $domain);
        
        if (!$user) {
            // Не удалось получить данные пользователя — запрещаем доступ
            $this->logger->logAccessCheck($userId, $userDepartments, 'denied', 'user_data_error');
            return false;
        }
        
        // Проверка статуса администратора
        if ($this->userService->isAdmin($user, $authId, $domain)) {
            // Администраторы всегда имеют доступ
            $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'admin');
            return true;
        }
        
        // Получаем конфигурацию прав доступа
        $accessConfig = $this->configService->getAccessConfig();
        
        // Если проверка отключена — доступ разрешён
        if (!isset($accessConfig['access_control']['enabled']) || !$accessConfig['access_control']['enabled']) {
            $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'check_disabled');
            return true;
        }
        
        $departments = $accessConfig['access_control']['departments'] ?? [];
        $users = $accessConfig['access_control']['users'] ?? [];
        
        // Если списки пустые — доступ запрещён
        if (empty($departments) && empty($users)) {
            $this->logger->logAccessCheck($userId, $userDepartments, 'denied', 'no_access_rules');
            return false;
        }
        
        // Проверка, есть ли отдел пользователя в списке
        if (!empty($userDepartments) && is_array($userDepartments)) {
            foreach ($userDepartments as $deptId) {
                foreach ($departments as $dept) {
                    if (isset($dept['id']) && $dept['id'] == $deptId) {
                        $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'department_in_list');
                        return true;
                    }
                }
            }
        }
        
        // Проверка, есть ли пользователь в списке
        foreach ($users as $user) {
            if (isset($user['id']) && $user['id'] == $userId) {
                $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'user_in_list');
                return true;
            }
        }
        
        // Доступ запрещён
        $this->logger->logAccessCheck($userId, $userDepartments, 'denied', 'not_in_lists');
        return false;
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
}

