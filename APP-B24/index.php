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
    
    // Подключение функции загрузки Vue.js
    require_once(__DIR__ . '/src/helpers/loadVueApp.php');
    
    // Логирование начала работы
    $logger->log('Index page access check', [
        'script' => 'index.php',
        'has_auth_params' => !empty($_REQUEST['AUTH_ID']) || !empty($_REQUEST['DOMAIN']),
        'timestamp' => date('Y-m-d H:i:s')
    ], 'info');
    
    // 2. Проверка конфигурации внешнего доступа
    $config = $configService->getIndexPageConfig();
    $externalAccessEnabled = isset($config['external_access']) && $config['external_access'] === true;
    
    $logger->log('Index page config check', [
        'external_access_enabled' => $externalAccessEnabled,
        'config_enabled' => $config['enabled'] ?? true
    ], 'info');
    
    // 3. Проверка авторизации Bitrix24 (если внешний доступ выключен)
    $authResult = false;
    if (!$externalAccessEnabled) {
        $authResult = $authService->checkBitrix24Auth();
        if (!$authResult) {
            // checkBitrix24Auth() уже выполнил редирект на public/failure.php
            $logger->logError('Index page auth check failed', [
                'external_access_enabled' => false
            ]);
            exit;
        }
        $logger->log('Index page auth check passed', [
            'external_access_enabled' => false
        ], 'info');
    } else {
        $logger->log('Index page external access enabled', [
            'skipping_auth_check' => true
        ], 'info');
    }
    
    // 4. Получение данных пользователя
    $authInfo = buildAuthInfo($authResult, $externalAccessEnabled, $userService, $logger);
    
    // 5. Построение данных для Vue.js
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
    
    // 6. Загрузка Vue.js приложения с данными
    // (Vue.js отобразит весь UI)
    loadVueApp('/', $vueAppData);
    
} catch (\Throwable $e) {
    // Единая точка обработки критических ошибок
    // ВАЖНО: Это единственное место, где PHP генерирует HTML.
    // Используется только для критических ошибок, когда Vue.js не может загрузиться.
    handleFatalError($e, $appEnv);
}

/**
 * Построение информации об авторизации
 * 
 * @param bool $authResult Результат проверки авторизации
 * @param bool $externalAccessEnabled Включен ли внешний доступ
 * @param UserService $userService Сервис работы с пользователями
 * @param LoggerService $logger Сервис логирования
 * @return array Информация об авторизации
 */
function buildAuthInfo(bool $authResult, bool $externalAccessEnabled, UserService $userService, LoggerService $logger): array
{
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
        
        if ($authId && $domain) {
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
                    
                    // Проверка прав администратора
                    $authInfo['is_admin'] = $userService->isAdmin($user, $authId, $domain);
                    
                    $logger->log('User data retrieved in index.php', [
                        'user_id' => $authInfo['user']['id'],
                        'is_admin' => $authInfo['is_admin']
                    ], 'info');
                }
            } catch (\Exception $e) {
                $logger->logError('Failed to get user info in index.php', [
                    'error' => $e->getMessage(),
                    'auth_id' => substr($authId, 0, 20) . '...',
                    'domain' => $domain
                ]);
            }
        } elseif ($externalAccessEnabled) {
            // Внешний доступ активен, но нет авторизации Bitrix24
            $authInfo['is_authenticated'] = false;
            $authInfo['external_access'] = true;
            
            $logger->log('External access enabled without Bitrix24 auth', [], 'info');
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
