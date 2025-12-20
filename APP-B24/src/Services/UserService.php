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
        $result = $this->apiService->getCurrentUser($authId, $domain);
        
        if (isset($result['error']) || !isset($result['result'])) {
            return null;
        }
        
        return $result['result'];
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
                return true;
            }
        }
        
        // Проверяем альтернативное поле IS_ADMIN
        if (isset($user['IS_ADMIN'])) {
            $isAdmin = ($user['IS_ADMIN'] === 'Y' || $user['IS_ADMIN'] == 1 || $user['IS_ADMIN'] === true);
            if ($isAdmin) {
                return true;
            }
        }
        
        // Если поле ADMIN отсутствует, используем метод user.admin для проверки
        return $this->apiService->checkIsAdmin($authId, $domain);
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
}

