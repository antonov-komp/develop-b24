<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * Загружает Vue.js приложение после проверки авторизации
 * Документация: https://context7.com/bitrix24/rest/
 */

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
    // Подключение и инициализация сервисов
    require_once(__DIR__ . '/src/bootstrap.php');
    
    // Подключение функции загрузки Vue.js
    require_once(__DIR__ . '/src/helpers/loadVueApp.php');
    
    // Проверка существования собранных файлов Vue.js
    $distPath = __DIR__ . '/public/dist/index.html';
    if (!file_exists($distPath)) {
        http_response_code(503);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body><h1>Vue.js приложение не собрано</h1><p>Выполните: <code>cd frontend && npm run build</code></p></body></html>');
    }
    
    // Получение конфигурации внешнего доступа
    $config = $configService->getIndexPageConfig();
    $externalAccessEnabled = isset($config['external_access']) && $config['external_access'] === true;
    
    // Проверка авторизации Bitrix24
    $authResult = $authService->checkBitrix24Auth();
    
    // Если внешний доступ активен, разрешаем доступ без Bitrix24
    if (!$authResult && !$externalAccessEnabled) {
        // checkBitrix24Auth() уже выполнил редирект на public/failure.php
        exit;
    }
    
    // Получение информации о текущей авторизации
    $authInfo = [
        'is_authenticated' => false,
        'user' => null,
        'is_admin' => false,
        'domain' => null,
        'auth_id' => null
    ];
    
    // Если авторизация прошла, получаем данные пользователя
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
        }
    }
    
    // Передача данных в Vue.js через модификацию loadVueApp
    // Создаём временную функцию для передачи данных
    $vueAppData = [
        'authInfo' => $authInfo,
        'externalAccessEnabled' => $externalAccessEnabled
    ];
    
    // Сохраняем данные в глобальную переменную для использования в loadVueApp
    $GLOBALS['vue_app_data'] = $vueAppData;
    
    // Загрузка Vue.js приложения (главная страница)
    loadVueApp('/');
    
} catch (\Throwable $e) {
    error_log('Fatal error in index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body>';
    echo '<h1>Ошибка приложения</h1>';
    echo '<p>Произошла ошибка при загрузке страницы.</p>';
    if (ini_get('display_errors')) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    echo '</body></html>';
    exit;
}
