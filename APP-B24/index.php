<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * ВАЖНО: PHP не генерирует UI. Вся визуальная часть на Vue.js.
 * PHP только:
 * - Проверяет авторизацию
 * - Получает данные
 * - Передаёт данные в Vue.js
 * - Загружает Vue.js приложение
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */

use App\Services\UserService;
use App\Services\LoggerService;

// Определение окружения (development/production)
$appEnv = getenv('APP_ENV') ?: 'production';

// Условное включение отладочных настроек
if ($appEnv === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-errors.log');
}

try {
    // 1. Инициализация окружения
    // Подключение и инициализация сервисов
    require_once(__DIR__ . '/src/bootstrap.php');
    
    // Логирование начала работы
    $logger->log('Index page access check', [
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
    
    // 2. Получение маршрута из query параметра или URL
    $route = $_GET['route'] ?? '/';
    
    // Нормализация маршрута (убираем лишние слеши)
    $route = '/' . trim($route, '/');
    if ($route === '//') {
        $route = '/';
    }
    
    // 3. Проверка конфигурации внешнего доступа
    $config = $configService->getIndexPageConfig();
    $externalAccessEnabled = isset($config['external_access']) && $config['external_access'] === true;
    $appEnabled = isset($config['enabled']) && $config['enabled'] === true;
    $blockBitrix24Iframe = isset($config['block_bitrix24_iframe']) && $config['block_bitrix24_iframe'] === true;
    
    $logger->log('Index page config check', [
        'external_access_enabled' => $externalAccessEnabled,
        'config_enabled' => $appEnabled,
        'block_bitrix24_iframe' => $blockBitrix24Iframe,
        'route' => $route,
        'has_auth_id' => !empty($_REQUEST['AUTH_ID']),
        'has_domain' => !empty($_REQUEST['DOMAIN']),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ], 'info');
    
    // 3.1. Проверка, включено ли приложение
    // Если приложение отключено, показываем страницу ошибки БЕЗ загрузки Vue.js
    if (!$appEnabled) {
        $message = $config['message'] ?? 'Интерфейс приложения временно недоступен. Пожалуйста, попробуйте позже.';
        $lastUpdated = $config['last_updated'] ?? null;
        
        $logger->log('Index page disabled by config', [
            'message' => $message,
            'last_updated' => $lastUpdated
        ], 'info');
        
        // Показываем страницу ошибки
        $errorPagePath = __DIR__ . '/templates/config-error.php';
        if (file_exists($errorPagePath)) {
            // Передаём переменные напрямую в шаблон
            // Шаблон использует $_GET, но мы можем установить их для совместимости
            $originalGet = $_GET;
            $_GET['message'] = $message;
            if ($lastUpdated) {
                $_GET['last_updated'] = $lastUpdated;
            }
            require_once($errorPagePath);
            // Восстанавливаем оригинальные GET-параметры (на всякий случай)
            $_GET = $originalGet;
        } else {
            // Если шаблон не найден, показываем простое сообщение
            http_response_code(503);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Интерфейс недоступен</title></head><body>';
            echo '<h1>Интерфейс недоступен</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            if ($lastUpdated) {
                echo '<p><small>Последнее обновление: ' . htmlspecialchars($lastUpdated) . '</small></p>';
            }
            echo '</body></html>';
        }
        exit;
    }
    
    // 4. Проверка авторизации Bitrix24
    $authResult = false;
    $hasUserToken = !empty($_REQUEST['AUTH_ID']) && !empty($_REQUEST['DOMAIN']);
    
    if (!$externalAccessEnabled) {
        // Внешний доступ выключен - требуется авторизация Bitrix24
        // Проверяем наличие токена пользователя (AUTH_ID и DOMAIN) - признак запроса из Bitrix24 iframe
        if (!$hasUserToken) {
            // Нет токена пользователя - это прямой доступ, блокируем
            $logger->logError('Index page blocked: external access disabled, no user token', [
                'external_access_enabled' => false,
                'has_auth_id' => !empty($_REQUEST['AUTH_ID']),
                'has_domain' => !empty($_REQUEST['DOMAIN']),
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'
            ]);
            
            // Редирект на страницу ошибки
            $authService->redirectToFailure('direct_access');
            exit;
        }
        
        // Есть токен пользователя - проверяем авторизацию через Bitrix24
        $authResult = $authService->checkBitrix24Auth();
        if (!$authResult) {
            // checkBitrix24Auth() уже выполнил редирект на public/failure.php
            $logger->logError('Index page auth check failed', [
                'external_access_enabled' => false,
                'has_user_token' => true
            ]);
            exit;
        }
        $logger->log('Index page auth check passed', [
            'external_access_enabled' => false,
            'access_type' => 'bitrix24_iframe'
        ], 'info');
    } else {
        // Внешний доступ включен - разрешаем прямой доступ БЕЗ обязательной авторизации
        // НО если включена блокировка Bitrix24 iframe и есть токен пользователя - блокируем доступ
        if ($hasUserToken && $blockBitrix24Iframe) {
            // Есть токен пользователя (запрос из Bitrix24 iframe) и включена блокировка - блокируем доступ
            $logger->logError('Index page blocked: external access enabled, but Bitrix24 iframe blocked', [
                'external_access_enabled' => true,
                'block_bitrix24_iframe' => true,
                'has_user_token' => true
            ]);
            
            // Редирект на страницу ошибки
            $authService->redirectToFailure('bitrix24_iframe_blocked');
            exit;
        }
        
        // Если есть токен пользователя и блокировка НЕ включена - проверяем авторизацию для использования данных пользователя
        if ($hasUserToken && !$blockBitrix24Iframe) {
            // Есть токен пользователя - проверяем авторизацию (для работы внутри Bitrix24 iframe)
            $authResult = $authService->checkBitrix24Auth();
            if ($authResult) {
                $logger->log('Index page external access enabled, auth check passed (Bitrix24 iframe)', [
                    'external_access_enabled' => true,
                    'block_bitrix24_iframe' => false,
                    'access_type' => 'bitrix24_iframe_with_external_access'
                ], 'info');
            } else {
                // Авторизация не прошла, но external_access включен - разрешаем доступ без данных пользователя
                $logger->log('Index page external access enabled, auth check failed but access allowed', [
                    'external_access_enabled' => true,
                    'has_user_token' => true,
                    'access_type' => 'external_access_fallback'
                ], 'warning');
            }
        } else {
            // Нет токена пользователя - это прямой доступ, разрешаем без авторизации
            $logger->log('Index page external access enabled, direct access allowed', [
                'external_access_enabled' => true,
                'block_bitrix24_iframe' => $blockBitrix24Iframe,
                'access_type' => 'direct_access'
            ], 'info');
        }
    }
    
    // 5. Получение данных пользователя (только для главной страницы)
    $vueAppData = null;
    if ($route === '/') {
        $authInfo = buildAuthInfo($authResult, $externalAccessEnabled, $blockBitrix24Iframe, $userService, $logger);
        
        // Построение данных для Vue.js
        $vueAppData = [
            'authInfo' => $authInfo,
            'externalAccessEnabled' => $externalAccessEnabled
        ];
        
        // Валидация данных перед передачей в Vue.js
        validateVueAppData($vueAppData, $logger);
        
        // Логирование данных перед передачей
        $logger->log('Index page data prepared for Vue.js', [
            'is_authenticated' => $authInfo['is_authenticated'] ?? false,
            'is_admin' => $authInfo['is_admin'] ?? false,
            'has_user' => !empty($authInfo['user']),
            'external_access' => $externalAccessEnabled
        ], 'info');
    }
    
    // 6. Загрузка Vue.js приложения через сервис
    // $vueAppService инициализирован в bootstrap.php
    $vueAppService->load($route, $vueAppData);
    
} catch (\Throwable $e) {
    // Единая точка обработки критических ошибок
    // ВАЖНО: Это единственное место, где PHP генерирует HTML.
    // Используется только для критических ошибок, когда Vue.js не может загрузиться.
    handleFatalError($e, $appEnv);
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
 * @param bool $externalAccessEnabled Включен ли внешний доступ
 * @param bool $blockBitrix24Iframe Заблокирован ли доступ из Bitrix24 iframe
 * @param UserService $userService Сервис работы с пользователями
 * @param LoggerService $logger Сервис логирования
 * @return array Информация об авторизации
 */
function buildAuthInfo(bool $authResult, bool $externalAccessEnabled, bool $blockBitrix24Iframe, UserService $userService, LoggerService $logger): array
{
    global $configService;
    
    $authInfo = [
        'is_authenticated' => false,
        'user' => null,
        'is_admin' => false,
        'domain' => null,
        'auth_id' => null
    ];
    
    // Если авторизация прошла или внешний доступ включен
    if ($authResult || $externalAccessEnabled) {
        $authId = $_REQUEST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_GET['APP_SID'] ?? null;
        $domain = $_REQUEST['DOMAIN'] ?? $_GET['DOMAIN'] ?? null;
        
        // Проверяем, есть ли токен пользователя в запросе (не токен установщика)
        $hasUserTokenInRequest = !empty($_REQUEST['AUTH_ID']) && !empty($_REQUEST['DOMAIN']);
        
        // Режим 3: Только внешний с токеном админа
        // Если блокирован Bitrix24 iframe и нет токена пользователя в запросе - используем токен админа из settings.json
        if ($externalAccessEnabled && $blockBitrix24Iframe && !$hasUserTokenInRequest) {
            $settings = $configService->getSettings();
            $adminToken = $settings['access_token'] ?? null;
            $adminDomain = $settings['domain'] ?? null;
            
            if ($adminToken && $adminDomain) {
                $authId = $adminToken;
                $domain = $adminDomain;
                
                $logger->log('Using admin token from settings.json for external-only mode', [
                    'token_length' => strlen($adminToken),
                    'domain' => $adminDomain
                ], 'info');
                
                // Получаем данные администратора
                try {
                    $user = $userService->getCurrentUser($authId, $domain);
                    if ($user) {
                        $authInfo['is_authenticated'] = true;
                        $authInfo['auth_id'] = $authId;
                        $authInfo['domain'] = $domain;
                        $authInfo['user'] = [
                            'id' => $user['ID'] ?? null,
                            'name' => $user['NAME'] ?? '',
                            'last_name' => $user['LAST_NAME'] ?? '',
                            'full_name' => $userService->getUserFullName($user),
                            'email' => $user['EMAIL'] ?? '',
                            'admin' => $user['ADMIN'] ?? 'N'
                        ];
                        $authInfo['is_admin'] = $userService->isAdmin($user, $authId, $domain);
                        
                        $logger->log('Admin user data retrieved using token from settings.json', [
                            'user_id' => $authInfo['user']['id'],
                            'is_admin' => $authInfo['is_admin']
                        ], 'info');
                    } else {
                        // Токен валиден, но данные пользователя не получены
                        $logger->log('Admin token valid but user data not retrieved - continuing without user data', [
                            'token_length' => strlen($adminToken),
                            'domain' => $adminDomain
                        ], 'warning');
                        $authInfo['is_authenticated'] = false;
                        $authInfo['external_access'] = true;
                        $authInfo['auth_id'] = $adminToken;
                        $authInfo['domain'] = $adminDomain;
                    }
                } catch (\Exception $e) {
                    $logger->logError('Failed to get admin user info using token from settings.json', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // В режиме только внешнего доступа продолжаем без данных пользователя
                    // Но всё равно разрешаем доступ
                    $authInfo['is_authenticated'] = false;
                    $authInfo['external_access'] = true;
                    $authInfo['auth_id'] = $adminToken; // Сохраняем токен для возможного использования
                    $authInfo['domain'] = $adminDomain;
                }
            } else {
                // Нет токена админа в settings.json - работаем без авторизации
                $authInfo['is_authenticated'] = false;
                $authInfo['external_access'] = true;
                $logger->log('External-only mode enabled, but no admin token in settings.json - working without auth', [], 'warning');
            }
        }
        // Если режим 3 не сработал, но external_access включен и block_bitrix24_iframe включен
        // Это fallback для случая, когда что-то пошло не так
        elseif ($externalAccessEnabled && $blockBitrix24Iframe) {
            // Режим 3, но что-то пошло не так - работаем без авторизации
            $authInfo['is_authenticated'] = false;
            $authInfo['external_access'] = true;
            $logger->log('External-only mode fallback: working without authentication', [], 'info');
        }
        // Режим 2: Везде - если есть токен пользователя, используем его
        elseif ($hasUserTokenInRequest && $authId && $domain) {
            // Есть токен пользователя в запросе - используем его
            $authInfo['is_authenticated'] = true;
            $authInfo['auth_id'] = $authId;
            $authInfo['domain'] = $domain;
            
            // Получаем данные пользователя
            try {
                $user = $userService->getCurrentUser($authId, $domain);
                if ($user) {
                    $authInfo['user'] = [
                        'id' => $user['ID'] ?? null,
                        'name' => $user['NAME'] ?? '',
                        'last_name' => $user['LAST_NAME'] ?? '',
                        'full_name' => $userService->getUserFullName($user),
                        'email' => $user['EMAIL'] ?? '',
                        'admin' => $user['ADMIN'] ?? 'N'
                    ];
                    $authInfo['is_admin'] = $userService->isAdmin($user, $authId, $domain);
                    
                    $logger->log('User data retrieved in index.php', [
                        'user_id' => $authInfo['user']['id'],
                        'is_admin' => $authInfo['is_admin'],
                        'using_user_token' => true
                    ], 'info');
                }
            } catch (\Exception $e) {
                $logger->logError('Failed to get user info in index.php', [
                    'error' => $e->getMessage(),
                    'auth_id' => substr($authId, 0, 20) . '...',
                    'domain' => $domain
                ]);
            }
        }
        // Режим 2: Везде - если нет токена пользователя, работаем без авторизации
        elseif ($externalAccessEnabled && !$blockBitrix24Iframe) {
            // Внешний доступ включен, но Bitrix24 iframe не заблокирован - работаем без авторизации
            $authInfo['is_authenticated'] = false;
            $authInfo['external_access'] = true;
            
            $logger->log('External access enabled, working without authentication', [], 'info');
        }
        // Если авторизация прошла (режим 1: Только Bitrix24)
        elseif ($authResult) {
            // Авторизация прошла - используем токен из запроса
            if ($authId && $domain) {
                $authInfo['is_authenticated'] = true;
                $authInfo['auth_id'] = $authId;
                $authInfo['domain'] = $domain;
                
                try {
                    $user = $userService->getCurrentUser($authId, $domain);
                    if ($user) {
                        $authInfo['user'] = [
                            'id' => $user['ID'] ?? null,
                            'name' => $user['NAME'] ?? '',
                            'last_name' => $user['LAST_NAME'] ?? '',
                            'full_name' => $userService->getUserFullName($user),
                            'email' => $user['EMAIL'] ?? '',
                            'admin' => $user['ADMIN'] ?? 'N'
                        ];
                        $authInfo['is_admin'] = $userService->isAdmin($user, $authId, $domain);
                    }
                } catch (\Exception $e) {
                    $logger->logError('Failed to get user info in index.php', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    return $authInfo;
}

/**
 * Валидация данных перед передачей в Vue.js
 * 
 * @param array $data Данные для валидации
 * @param LoggerService $logger Сервис логирования
 * @return void
 * @throws \InvalidArgumentException При невалидных данных
 */
function validateVueAppData(array $data, LoggerService $logger): void
{
    if (!isset($data['authInfo'])) {
        $logger->logError('Vue app data validation failed: authInfo is required', [
            'data_keys' => array_keys($data)
        ]);
        throw new \InvalidArgumentException('authInfo is required');
    }
    
    if (!is_array($data['authInfo'])) {
        $logger->logError('Vue app data validation failed: authInfo must be an array', [
            'authInfo_type' => gettype($data['authInfo'])
        ]);
        throw new \InvalidArgumentException('authInfo must be an array');
    }
    
    // Проверка обязательных полей в authInfo
    $requiredFields = ['is_authenticated', 'is_admin'];
    foreach ($requiredFields as $field) {
        if (!isset($data['authInfo'][$field])) {
            $logger->logError('Vue app data validation failed: missing field in authInfo', [
                'field' => $field
            ]);
            throw new \InvalidArgumentException("authInfo.{$field} is required");
        }
    }
    
    $logger->log('Vue app data validation passed', [
        'has_authInfo' => true,
        'has_externalAccessEnabled' => isset($data['externalAccessEnabled'])
    ], 'info');
}

/**
 * Обработка фатальных ошибок
 * 
 * ВАЖНО: Это единственное место, где PHP генерирует HTML.
 * Используется только для критических ошибок, когда Vue.js не может загрузиться.
 * Все остальные ошибки должны обрабатываться в Vue.js.
 * 
 * @param \Throwable $e Исключение
 * @param string $appEnv Окружение (development/production)
 * @return void
 */
function handleFatalError(\Throwable $e, string $appEnv): void
{
    error_log('Fatal error in index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body>';
    echo '<h1>Ошибка приложения</h1>';
    echo '<p>Произошла критическая ошибка при загрузке страницы.</p>';
    
    if ($appEnv === 'development' || ini_get('display_errors')) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    
    echo '</body></html>';
    exit;
}
