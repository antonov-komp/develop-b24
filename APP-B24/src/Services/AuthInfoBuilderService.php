<?php

namespace App\Services;

use App\Helpers\DomainResolver;

/**
 * Сервис построения информации об авторизации
 * 
 * Обрабатывает все режимы доступа и строит authInfo для Vue.js
 * Вынесено из функции buildAuthInfo() в index.php (строки 325-519)
 * Документация: https://context7.com/bitrix24/rest/
 */
class AuthInfoBuilderService
{
    protected AuthService $authService;
    protected UserService $userService;
    protected ConfigService $configService;
    protected AccessModeService $accessModeService;
    protected DomainResolver $domainResolver;
    protected LoggerService $logger;
    
    public function __construct(
        AuthService $authService,
        UserService $userService,
        ConfigService $configService,
        AccessModeService $accessModeService,
        DomainResolver $domainResolver,
        LoggerService $logger
    ) {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->configService = $configService;
        $this->accessModeService = $accessModeService;
        $this->domainResolver = $domainResolver;
        $this->logger = $logger;
    }
    
    /**
     * Построение информации об авторизации
     * 
     * Режимы работы:
     * 1. Только Bitrix24: external_access=false - использует токен пользователя из запроса
     * 2. Везде: external_access=true, block_bitrix24_iframe=false - работает везде, использует токен пользователя если есть
     * 3. Только внешний с токеном админа: external_access=true, block_bitrix24_iframe=true - использует токен админа из settings.json
     * 
     * @param bool $authResult Результат проверки авторизации
     * @param array $accessMode Режим доступа
     * @return array Информация об авторизации
     */
    public function build(bool $authResult, array $accessMode): array
    {
        $authInfo = [
            'is_authenticated' => false,
            'user' => null,
            'is_admin' => false,
            'domain' => null,
            'auth_id' => null
        ];
        
        $externalAccessEnabled = $accessMode['external_access_enabled'] ?? false;
        $blockBitrix24Iframe = $accessMode['block_bitrix24_iframe'] ?? false;
        
        // Если авторизация прошла или внешний доступ включен
        if ($authResult || $externalAccessEnabled) {
            $authId = $_REQUEST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_GET['APP_SID'] ?? null;
            $domain = $_REQUEST['DOMAIN'] ?? $_GET['DOMAIN'] ?? null;
            
            // Если домен не найден в запросе, пытаемся получить через DomainResolver
            if (!$domain) {
                $domain = $this->domainResolver->resolveDomain();
            }
            
            // Проверяем, есть ли токен пользователя в запросе (не токен установщика)
            $hasUserTokenInRequest = $this->accessModeService->hasUserTokenInRequest();
            
            // Режим 1: Только Bitrix24 - если авторизация прошла и внешний доступ выключен
            if ($authResult && !$externalAccessEnabled && $hasUserTokenInRequest && $authId && $domain) {
                return $this->buildBitrix24OnlyMode($authInfo, $authId, $domain);
            }
            
            // Режим 3: Только внешний с токеном админа
            // Если блокирован Bitrix24 iframe и нет токена пользователя в запросе - используем токен админа из settings.json
            if ($externalAccessEnabled && $blockBitrix24Iframe && !$hasUserTokenInRequest) {
                return $this->buildExternalOnlyMode($authInfo);
            }
            
            // Если режим 3 не сработал, но external_access включен и block_bitrix24_iframe включен
            // Это fallback для случая, когда что-то пошло не так
            if ($externalAccessEnabled && $blockBitrix24Iframe) {
                $authInfo['is_authenticated'] = false;
                $authInfo['external_access'] = true;
                $this->logger->log('External-only mode fallback: working without authentication', [], 'info');
                return $authInfo;
            }
            
            // Режим 2: Везде - если есть токен пользователя, используем его
            if ($hasUserTokenInRequest && $authId && $domain) {
                return $this->buildEverywhereModeWithToken($authInfo, $authId, $domain);
            }
            
            // Режим 2: Везде - если нет токена пользователя, работаем без авторизации
            if ($externalAccessEnabled && !$blockBitrix24Iframe) {
                $authInfo['is_authenticated'] = false;
                $authInfo['external_access'] = true;
                $this->logger->log('External access enabled, working without authentication', [], 'info');
                return $authInfo;
            }
        }
        
        return $authInfo;
    }
    
    /**
     * Построение authInfo для режима "Только Bitrix24"
     * 
     * @param array $authInfo Базовая структура authInfo
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array Обновлённая authInfo
     */
    protected function buildBitrix24OnlyMode(array $authInfo, string $authId, string $domain): array
    {
        // Авторизация прошла - используем токен из запроса
        $authInfo['is_authenticated'] = true;
        $authInfo['auth_id'] = $authId;
        $authInfo['domain'] = $domain;
        
        try {
            $user = $this->userService->getCurrentUser($authId, $domain);
            if ($user) {
                // Получаем данные пользователя с отделами и фото
                $userData = $this->userService->getUserDataWithDepartments($user, $authId, $domain);
                $authInfo['user'] = $userData['user'] ?? $userData;
                $authInfo['departments'] = $userData['departments'] ?? [];
                $authInfo['is_admin'] = $this->userService->isAdmin($user, $authId, $domain);
                
                $this->logger->log('User data retrieved (Bitrix24 only mode)', [
                    'user_id' => $authInfo['user']['ID'] ?? $authInfo['user']['id'],
                    'is_admin' => $authInfo['is_admin'],
                    'using_user_token' => true,
                    'has_departments' => !empty($authInfo['departments']),
                    'departments_count' => isset($authInfo['departments']) ? count($authInfo['departments']) : 0,
                    'departments' => $authInfo['departments'] ?? []
                ], 'info');
            } else {
                $this->logger->logError('User data not retrieved in Bitrix24 only mode', [
                    'auth_id_length' => strlen($authId),
                    'domain' => $domain
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->logError('Failed to get user info (Bitrix24 only mode)', [
                'error' => $e->getMessage(),
                'auth_id' => substr($authId, 0, 20) . '...',
                'domain' => $domain
            ]);
        }
        
        return $authInfo;
    }
    
    /**
     * Построение authInfo для режима "Только внешний"
     * 
     * @param array $authInfo Базовая структура authInfo
     * @return array Обновлённая authInfo
     */
    protected function buildExternalOnlyMode(array $authInfo): array
    {
        $settings = $this->configService->getSettings();
        $adminToken = $settings['access_token'] ?? null;
        $adminDomain = $settings['domain'] ?? null;
        
        if ($adminToken && $adminDomain) {
            $authId = $adminToken;
            $domain = $adminDomain;
            
            $this->logger->log('Using admin token from settings.json for external-only mode', [
                'token_length' => strlen($adminToken),
                'domain' => $adminDomain
            ], 'info');
            
            // Получаем данные администратора
            // Сначала проверяем, есть ли сохранённые данные администратора в settings.json
            $adminUserData = $settings['admin_user'] ?? null;
            
            if ($adminUserData && is_array($adminUserData) && !empty($adminUserData['id'])) {
                // Используем сохранённые данные администратора
                // При внешнем доступе с токеном установщика пользователь считается администратором
                $authInfo['is_authenticated'] = true;
                $authInfo['auth_id'] = $adminToken;
                $authInfo['domain'] = $adminDomain;
                // Передаём данные в формате Bitrix24 API (верхний регистр) для совместимости с компонентом
                $fullName = trim(($adminUserData['name'] ?? '') . ' ' . ($adminUserData['last_name'] ?? ''));
                $authInfo['user'] = [
                    'ID' => $adminUserData['id'] ?? null,
                    'NAME' => $adminUserData['name'] ?? '',
                    'LAST_NAME' => $adminUserData['last_name'] ?? '',
                    'FULL_NAME' => $fullName,
                    'EMAIL' => $adminUserData['email'] ?? '',
                    'ADMIN' => 'Y', // При внешнем доступе с токеном установщика всегда администратор
                    // Также сохраняем в нижнем регистре для совместимости
                    'id' => $adminUserData['id'] ?? null,
                    'name' => $adminUserData['name'] ?? '',
                    'last_name' => $adminUserData['last_name'] ?? '',
                    'full_name' => $fullName,
                    'email' => $adminUserData['email'] ?? '',
                    'admin' => 'Y' // При внешнем доступе с токеном установщика всегда администратор
                ];
                $authInfo['is_admin'] = true; // При внешнем доступе с токеном установщика всегда администратор
                
                $this->logger->log('Admin user data retrieved from saved settings.json', [
                    'user_id' => $authInfo['user']['ID'] ?? $authInfo['user']['id'],
                    'is_admin' => $authInfo['is_admin'],
                    'user_name' => $authInfo['user']['full_name']
                ], 'info');
            } else {
                // Сохранённых данных нет - работаем без данных пользователя
                // Токен установщика не может использоваться для user.current (ошибка WRONG_CLIENT)
                $this->logger->log('No saved admin user data in settings.json - working without user data', [
                    'token_length' => strlen($adminToken),
                    'domain' => $adminDomain,
                    'note' => 'Installer token cannot be used for user.current API call'
                ], 'warning');
                $authInfo['is_authenticated'] = false;
                $authInfo['external_access'] = true;
                $authInfo['auth_id'] = $adminToken;
                $authInfo['domain'] = $adminDomain;
            }
        } else {
            // Нет токена админа в settings.json - работаем без авторизации
            $authInfo['is_authenticated'] = false;
            $authInfo['external_access'] = true;
            $this->logger->log('External-only mode enabled, but no admin token in settings.json - working without auth', [], 'warning');
        }
        
        return $authInfo;
    }
    
    /**
     * Построение authInfo для режима "Везде" с токеном пользователя
     * 
     * @param array $authInfo Базовая структура authInfo
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array Обновлённая authInfo
     */
    protected function buildEverywhereModeWithToken(array $authInfo, string $authId, string $domain): array
    {
        // Есть токен пользователя в запросе - используем его
        $authInfo['is_authenticated'] = true;
        $authInfo['auth_id'] = $authId;
        $authInfo['domain'] = $domain;
        
        // Получаем данные пользователя
        try {
            $user = $this->userService->getCurrentUser($authId, $domain);
            if ($user) {
                // Получаем данные пользователя с отделами и фото
                $userData = $this->userService->getUserDataWithDepartments($user, $authId, $domain);
                $authInfo['user'] = $userData['user'];
                $authInfo['departments'] = $userData['departments'];
                $authInfo['is_admin'] = $this->userService->isAdmin($user, $authId, $domain);
                
                $this->logger->log('User data retrieved (everywhere mode with token)', [
                    'user_id' => $authInfo['user']['ID'] ?? $authInfo['user']['id'],
                    'is_admin' => $authInfo['is_admin'],
                    'using_user_token' => true,
                    'departments_count' => count($authInfo['departments'])
                ], 'info');
            }
        } catch (\Exception $e) {
            $this->logger->logError('Failed to get user info (everywhere mode with token)', [
                'error' => $e->getMessage(),
                'auth_id' => substr($authId, 0, 20) . '...',
                'domain' => $domain
            ]);
        }
        
        return $authInfo;
    }
}

