<?php

namespace App\Helpers;

use App\Services\Bitrix24ApiService;

/**
 * Вспомогательный класс для проверки статуса администратора
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class AdminChecker
{
    protected Bitrix24ApiService $apiService;
    
    public function __construct(Bitrix24ApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * Проверка статуса администратора
     * 
     * @param array $user Данные пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return bool true если администратор
     */
    public function check(array $user, string $authId, string $domain): bool
    {
        // Проверка поля ADMIN в данных пользователя
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
        
        // Проверка альтернативного поля IS_ADMIN
        if (isset($user['IS_ADMIN'])) {
            $isAdmin = ($user['IS_ADMIN'] === 'Y' || $user['IS_ADMIN'] == 1 || $user['IS_ADMIN'] === true);
            if ($isAdmin) {
                return true;
            }
        }
        
        // Проверка через user.admin API
        return $this->apiService->checkIsAdmin($authId, $domain);
    }
}




