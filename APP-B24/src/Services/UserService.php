<?php

namespace App\Services;

/**
 * Сервис для работы с пользователями
 * 
 * Обеспечивает работу с данными пользователей Bitrix24,
 * проверку статуса администратора и получение отделов пользователя
 * Документация: https://context7.com/bitrix24/rest/
 */
class UserService
{
    protected Bitrix24ApiService $apiService;
    protected LoggerService $logger;
    
    public function __construct(Bitrix24ApiService $apiService, LoggerService $logger)
    {
        $this->apiService = $apiService;
        $this->logger = $logger;
    }
    
    /**
     * Получение текущего пользователя
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Данные пользователя или null при ошибке
     */
    public function getCurrentUser(string $authId, string $domain): ?array
    {
        // Bitrix24ApiService::getCurrentUser() уже возвращает данные пользователя напрямую (или null)
        $user = $this->apiService->getCurrentUser($authId, $domain);
        
        // Логирование для отладки
        $this->logger->log('UserService::getCurrentUser result', [
            'has_user' => !is_null($user),
            'is_array' => is_array($user),
            'is_empty' => is_array($user) && empty($user),
            'user_keys' => is_array($user) ? array_keys($user) : [],
            'user_id' => is_array($user) && isset($user['ID']) ? $user['ID'] : null
        ], 'info');
        
        // Проверяем, что получили валидные данные пользователя
        if (!$user || !is_array($user) || empty($user)) {
            $this->logger->logError('UserService::getCurrentUser returned null or invalid data', [
                'user_type' => gettype($user),
                'is_array' => is_array($user),
                'is_empty' => is_array($user) && empty($user)
            ]);
            return null;
        }
        
        return $user;
    }
    
    /**
     * Получение пользователя по ID
     * 
     * @param int $userId ID пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Данные пользователя или null при ошибке
     */
    public function getUserById(int $userId, string $authId, string $domain): ?array
    {
        return $this->apiService->getUser($userId, $authId, $domain);
    }
    
    /**
     * Проверка статуса администратора
     * 
     * Проверяет поле ADMIN в данных пользователя и, при необходимости,
     * использует метод user.admin для дополнительной проверки
     * 
     * @param array $user Данные пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return bool true если администратор
     */
    public function isAdmin(array $user, string $authId, string $domain): bool
    {
        // Логирование для отладки
        $this->logger->log('UserService::isAdmin check', [
            'user_id' => $user['ID'] ?? null,
            'has_admin_field' => isset($user['ADMIN']),
            'admin_value' => $user['ADMIN'] ?? null,
            'has_is_admin_field' => isset($user['IS_ADMIN']),
            'is_admin_value' => $user['IS_ADMIN'] ?? null
        ], 'info');
        
        // Сначала проверяем поле ADMIN в данных пользователя
        if (isset($user['ADMIN'])) {
            $adminValue = $user['ADMIN'];
            $isAdmin = (
                $adminValue === 'Y' || 
                $adminValue === 'y' || 
                $adminValue == 1 || 
                $adminValue === 1 || 
                $adminValue === true ||
                $adminValue === '1'
            );
            
            if ($isAdmin) {
                $this->logger->log('UserService::isAdmin - admin from ADMIN field', [
                    'user_id' => $user['ID'] ?? null,
                    'admin_value' => $adminValue
                ], 'info');
                return true;
            }
        }
        
        // Проверяем альтернативное поле IS_ADMIN
        if (isset($user['IS_ADMIN'])) {
            $isAdmin = ($user['IS_ADMIN'] === 'Y' || $user['IS_ADMIN'] == 1 || $user['IS_ADMIN'] === true);
            if ($isAdmin) {
                $this->logger->log('UserService::isAdmin - admin from IS_ADMIN field', [
                    'user_id' => $user['ID'] ?? null,
                    'is_admin_value' => $user['IS_ADMIN']
                ], 'info');
                return true;
            }
        }
        
        // Если поле ADMIN отсутствует, используем метод user.admin для проверки
        $this->logger->log('UserService::isAdmin - checking via user.admin API', [
            'user_id' => $user['ID'] ?? null
        ], 'info');
        $isAdmin = $this->apiService->checkIsAdmin($authId, $domain);
        $this->logger->log('UserService::isAdmin - result from user.admin API', [
            'user_id' => $user['ID'] ?? null,
            'is_admin' => $isAdmin
        ], 'info');
        return $isAdmin;
    }
    
    /**
     * Получение отделов пользователя
     * 
     * Извлекает массив ID отделов из поля UF_DEPARTMENT пользователя
     * 
     * @param array $user Данные пользователя
     * @return array Массив ID отделов
     */
    public function getUserDepartments(array $user): array
    {
        if (!isset($user['UF_DEPARTMENT'])) {
            return [];
        }
        
        $departments = $user['UF_DEPARTMENT'];
        
        // Если это массив
        if (is_array($departments)) {
            return array_map('intval', $departments);
        }
        
        // Если это одно значение
        if (is_numeric($departments)) {
            return [(int)$departments];
        }
        
        return [];
    }
    
    /**
     * Получение полного имени пользователя
     * 
     * @param array $user Данные пользователя
     * @return string Полное имя пользователя
     */
    public function getUserFullName(array $user): string
    {
        $userName = $user['NAME'] ?? '';
        $userLastName = $user['LAST_NAME'] ?? '';
        $userFullName = trim($userName . ' ' . $userLastName);
        
        if (empty($userFullName)) {
            $userId = $user['ID'] ?? 'неизвестен';
            $userFullName = 'Пользователь #' . $userId;
        }
        
        return $userFullName;
    }
    
    /**
     * Получение URL фото пользователя
     * 
     * @param array $user Данные пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return string|null URL фото пользователя или null
     */
    public function getUserPhotoUrl(array $user, string $authId, string $domain): ?string
    {
        if (empty($user['PERSONAL_PHOTO'])) {
            return null;
        }
        
        // PERSONAL_PHOTO может быть ID файла или URL
        if (is_numeric($user['PERSONAL_PHOTO'])) {
            // Это ID файла, формируем URL для скачивания
            return 'https://' . $domain . '/rest/download?auth=' . $authId . '&id=' . $user['PERSONAL_PHOTO'];
        }
        
        // Это уже URL
        return $user['PERSONAL_PHOTO'];
    }
    
    /**
     * Получает данные пользователя с отделами и фото
     * 
     * Вынесено из функции getUserDataWithDepartments() в index.php (строки 245-323)
     * 
     * @param array $user Данные пользователя из API
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array Массив с данными пользователя и отделами ['user' => [...], 'departments' => [...]]
     */
    public function getUserDataWithDepartments(array $user, string $authId, string $domain): array
    {
        // Получаем ID отделов пользователя
        $userDepartmentIds = $this->getUserDepartments($user);
        $userDepartments = [];
        
        $this->logger->log('UserService::getUserDataWithDepartments: Getting user departments', [
            'user_id' => $user['ID'] ?? null,
            'uf_department_raw' => $user['UF_DEPARTMENT'] ?? null,
            'department_ids' => $userDepartmentIds,
            'department_ids_count' => count($userDepartmentIds)
        ], 'info');
        
        // Получаем названия отделов по их ID
        if (!empty($userDepartmentIds)) {
            foreach ($userDepartmentIds as $deptId) {
                $dept = $this->apiService->getDepartment($deptId, $authId, $domain);
                if ($dept) {
                    $userDepartments[] = [
                        'id' => (int)$deptId,
                        'name' => $dept['NAME'] ?? 'Без названия'
                    ];
                    $this->logger->log('UserService::getUserDataWithDepartments: Department found', [
                        'dept_id' => $deptId,
                        'dept_name' => $dept['NAME'] ?? 'Без названия'
                    ], 'info');
                } else {
                    $this->logger->log('UserService::getUserDataWithDepartments: Department not found', [
                        'dept_id' => $deptId
                    ], 'warning');
                }
            }
        } else {
            $this->logger->log('UserService::getUserDataWithDepartments: No department IDs found', [
                'user_id' => $user['ID'] ?? null,
                'has_uf_department' => isset($user['UF_DEPARTMENT'])
            ], 'info');
        }
        
        // Получаем URL фото пользователя (если есть)
        $personalPhoto = $this->getUserPhotoUrl($user, $authId, $domain);
        
        // Передаём данные в формате Bitrix24 API (верхний регистр) для совместимости с компонентом
        $userData = [
            'ID' => $user['ID'] ?? null,
            'NAME' => $user['NAME'] ?? '',
            'LAST_NAME' => $user['LAST_NAME'] ?? '',
            'FULL_NAME' => $this->getUserFullName($user),
            'EMAIL' => $user['EMAIL'] ?? '',
            'ADMIN' => $user['ADMIN'] ?? 'N',
            'PERSONAL_PHOTO' => $personalPhoto,
            'UF_DEPARTMENT' => $user['UF_DEPARTMENT'] ?? null,
            // Также сохраняем в нижнем регистре для совместимости
            'id' => $user['ID'] ?? null,
            'name' => $user['NAME'] ?? '',
            'last_name' => $user['LAST_NAME'] ?? '',
            'full_name' => $this->getUserFullName($user),
            'email' => $user['EMAIL'] ?? '',
            'admin' => $user['ADMIN'] ?? 'N',
            'personal_photo' => $personalPhoto,
            'uf_department' => $user['UF_DEPARTMENT'] ?? null
        ];
        
        return [
            'user' => $userData,
            'departments' => $userDepartments
        ];
    }
}


