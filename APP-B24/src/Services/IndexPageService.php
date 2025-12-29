<?php

namespace App\Services;

/**
 * Основной сервис обработки запроса к index.php
 * 
 * Координирует работу всех сервисов для обработки запроса
 * Документация: https://context7.com/bitrix24/rest/
 */
class IndexPageService
{
    protected RouteService $routeService;
    protected ConfigValidatorService $configValidatorService;
    protected ConfigService $configService;
    protected AccessModeService $accessModeService;
    protected AuthService $authService;
    protected AuthInfoBuilderService $authInfoBuilderService;
    protected VueAppService $vueAppService;
    protected ErrorHandlerService $errorHandlerService;
    protected LoggerService $logger;
    
    public function __construct(
        RouteService $routeService,
        ConfigValidatorService $configValidatorService,
        ConfigService $configService,
        AccessModeService $accessModeService,
        AuthService $authService,
        AuthInfoBuilderService $authInfoBuilderService,
        VueAppService $vueAppService,
        ErrorHandlerService $errorHandlerService,
        LoggerService $logger
    ) {
        $this->routeService = $routeService;
        $this->configValidatorService = $configValidatorService;
        $this->configService = $configService;
        $this->accessModeService = $accessModeService;
        $this->authService = $authService;
        $this->authInfoBuilderService = $authInfoBuilderService;
        $this->vueAppService = $vueAppService;
        $this->errorHandlerService = $errorHandlerService;
        $this->logger = $logger;
    }
    
    /**
     * Обработка запроса к index.php
     * 
     * Координирует работу всех сервисов:
     * 1. Получение маршрута
     * 2. Валидация конфигурации
     * 3. Определение режима доступа
     * 4. Проверка авторизации
     * 5. Построение данных для Vue.js (только для главной страницы)
     * 6. Загрузка Vue.js приложения
     * 
     * @return void
     * @throws \Exception При критических ошибках
     */
    public function handle(): void
    {
        // Логирование начала работы
        $this->logger->log('Index page access check', [
            'script' => 'index.php',
            'has_auth_params' => !empty($_REQUEST['AUTH_ID']) || !empty($_REQUEST['APP_SID']) || !empty($_REQUEST['DOMAIN']),
            'has_auth_id' => !empty($_REQUEST['AUTH_ID']),
            'has_app_sid' => !empty($_REQUEST['APP_SID']),
            'has_domain' => !empty($_REQUEST['DOMAIN']),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'post_data_keys' => !empty($_POST) ? array_keys($_POST) : [],
            'get_data_keys' => !empty($_GET) ? array_keys($_GET) : [],
            'timestamp' => date('Y-m-d H:i:s')
        ], 'info');
        
        // 1. Получение маршрута
        $route = $this->routeService->getRoute();
        
        // 2. Валидация конфигурации
        $config = $this->configValidatorService->validateIndexPageConfig();
        
        $this->logger->log('Index page config check', [
            'external_access_enabled' => $config['external_access'] ?? false,
            'config_enabled' => $config['enabled'] ?? true,
            'block_bitrix24_iframe' => $config['block_bitrix24_iframe'] ?? false,
            'route' => $route,
            'has_auth_id' => !empty($_REQUEST['AUTH_ID']),
            'has_domain' => !empty($_REQUEST['DOMAIN']),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ], 'info');
        
        // 2.1. Проверка, включено ли приложение
        if (!$this->configValidatorService->checkAppEnabled()) {
            $this->configValidatorService->renderConfigErrorPage($config);
            return; // exit уже вызван в renderConfigErrorPage
        }
        
        // 3. Определение режима доступа
        $accessMode = $this->accessModeService->determineAccessMode($config);
        
        // 4. Проверка авторизации
        $authResult = $this->checkAuthorization($accessMode);
        
        // 5. Построение данных для Vue.js (только для главной страницы)
        $vueAppData = null;
        if ($route === '/') {
            $authInfo = $this->authInfoBuilderService->build($authResult, $accessMode);
            
            // Получение конфигурации логирования
            $loggingConfig = $this->configService->getLoggingConfig();
            
            // Построение данных для Vue.js
            $vueAppData = [
                'authInfo' => $authInfo,
                'externalAccessEnabled' => $accessMode['external_access_enabled'],
                'loggingConfig' => $loggingConfig
            ];
            
            // Валидация данных перед передачей в Vue.js
            $this->validateVueAppData($vueAppData);
            
            // Логирование данных перед передачей
            $this->logger->log('Index page data prepared for Vue.js', [
                'is_authenticated' => $authInfo['is_authenticated'] ?? false,
                'is_admin' => $authInfo['is_admin'] ?? false,
                'has_user' => !empty($authInfo['user']),
                'user_id' => $authInfo['user']['id'] ?? $authInfo['user']['ID'] ?? null,
                'user_name' => $authInfo['user']['full_name'] ?? $authInfo['user']['FULL_NAME'] ?? null,
                'user_email' => $authInfo['user']['email'] ?? $authInfo['user']['EMAIL'] ?? null,
                'has_departments' => !empty($authInfo['departments']),
                'departments_count' => isset($authInfo['departments']) ? count($authInfo['departments']) : 0,
                'departments' => $authInfo['departments'] ?? [],
                'external_access' => $accessMode['external_access_enabled'],
                'vueAppData_keys' => array_keys($vueAppData),
                'vueAppData_authInfo_keys' => isset($vueAppData['authInfo']) ? array_keys($vueAppData['authInfo']) : []
            ], 'info');
        }
        
        // 6. Загрузка Vue.js приложения
        $this->vueAppService->load($route, $vueAppData);
    }
    
    /**
     * Проверка авторизации в зависимости от режима доступа
     * 
     * @param array $accessMode Режим доступа
     * @return bool Результат проверки авторизации
     */
    protected function checkAuthorization(array $accessMode): bool
    {
        $externalAccessEnabled = $accessMode['external_access_enabled'] ?? false;
        $blockBitrix24Iframe = $accessMode['block_bitrix24_iframe'] ?? false;
        $hasUserToken = $this->accessModeService->hasUserTokenInRequest();
        
        if (!$externalAccessEnabled) {
            // Внешний доступ выключен - требуется авторизация Bitrix24
            // Проверяем наличие токена пользователя (AUTH_ID и DOMAIN) - признак запроса из Bitrix24 iframe
            if (!$hasUserToken) {
                // Нет токена пользователя - это прямой доступ, блокируем
                $this->logger->logError('Index page blocked: external access disabled, no user token', [
                    'external_access_enabled' => false,
                    'has_auth_id' => !empty($_REQUEST['AUTH_ID']),
                    'has_domain' => !empty($_REQUEST['DOMAIN']),
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'
                ]);
                
                // Редирект на страницу ошибки
                $this->authService->redirectToFailure('direct_access');
                exit;
            }
            
            // Есть токен пользователя - проверяем авторизацию через Bitrix24
            $authResult = $this->authService->checkBitrix24Auth();
            if (!$authResult) {
                // checkBitrix24Auth() уже выполнил редирект на public/failure.php
                $this->logger->logError('Index page auth check failed', [
                    'external_access_enabled' => false,
                    'has_user_token' => true
                ]);
                exit;
            }
            
            $this->logger->log('Index page auth check passed', [
                'external_access_enabled' => false,
                'access_type' => 'bitrix24_iframe'
            ], 'info');
            
            return true;
        } else {
            // Внешний доступ включен - разрешаем прямой доступ БЕЗ обязательной авторизации
            // НО если включена блокировка Bitrix24 iframe и есть токен пользователя - блокируем доступ
            if ($hasUserToken && $blockBitrix24Iframe) {
                // Есть токен пользователя (запрос из Bitrix24 iframe) и включена блокировка - блокируем доступ
                $this->logger->logError('Index page blocked: external access enabled, but Bitrix24 iframe blocked', [
                    'external_access_enabled' => true,
                    'block_bitrix24_iframe' => true,
                    'has_user_token' => true
                ]);
                
                // Редирект на страницу ошибки
                $this->authService->redirectToFailure('bitrix24_iframe_blocked');
                exit;
            }
            
            // Если есть токен пользователя и блокировка НЕ включена - проверяем авторизацию для использования данных пользователя
            if ($hasUserToken && !$blockBitrix24Iframe) {
                // Есть токен пользователя - проверяем авторизацию (для работы внутри Bitrix24 iframe)
                $authResult = $this->authService->checkBitrix24Auth();
                if ($authResult) {
                    $this->logger->log('Index page external access enabled, auth check passed (Bitrix24 iframe)', [
                        'external_access_enabled' => true,
                        'block_bitrix24_iframe' => false,
                        'access_type' => 'bitrix24_iframe_with_external_access'
                    ], 'info');
                    return true;
                } else {
                    // Авторизация не прошла, но external_access включен - разрешаем доступ без данных пользователя
                    $this->logger->log('Index page external access enabled, auth check failed but access allowed', [
                        'external_access_enabled' => true,
                        'has_user_token' => true,
                        'access_type' => 'external_access_fallback'
                    ], 'warning');
                    return false;
                }
            } else {
                // Нет токена пользователя - это прямой доступ, разрешаем без авторизации
                $this->logger->log('Index page external access enabled, direct access allowed', [
                    'external_access_enabled' => true,
                    'block_bitrix24_iframe' => $blockBitrix24Iframe,
                    'access_type' => 'direct_access'
                ], 'info');
                return false;
            }
        }
    }
    
    /**
     * Валидация данных перед передачей в Vue.js
     * 
     * Вынесено из функции validateVueAppData() в index.php (строки 521-560)
     * 
     * @param array $data Данные для валидации
     * @return void
     * @throws \InvalidArgumentException При невалидных данных
     */
    protected function validateVueAppData(array $data): void
    {
        if (!isset($data['authInfo'])) {
            $this->logger->logError('Vue app data validation failed: authInfo is required', [
                'data_keys' => array_keys($data)
            ]);
            throw new \InvalidArgumentException('authInfo is required');
        }
        
        if (!is_array($data['authInfo'])) {
            $this->logger->logError('Vue app data validation failed: authInfo must be an array', [
                'authInfo_type' => gettype($data['authInfo'])
            ]);
            throw new \InvalidArgumentException('authInfo must be an array');
        }
        
        // Проверка обязательных полей в authInfo
        $requiredFields = ['is_authenticated', 'is_admin'];
        foreach ($requiredFields as $field) {
            if (!isset($data['authInfo'][$field])) {
                $this->logger->logError('Vue app data validation failed: missing field in authInfo', [
                    'field' => $field
                ]);
                throw new \InvalidArgumentException("authInfo.{$field} is required");
            }
        }
        
        $this->logger->log('Vue app data validation passed', [
            'has_authInfo' => true,
            'has_externalAccessEnabled' => isset($data['externalAccessEnabled'])
        ], 'info');
    }
}

