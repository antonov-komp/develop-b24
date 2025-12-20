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
    
    // Проверка существования собранных файлов
    $distPath = __DIR__ . '/public/dist/index.html';
    if (!file_exists($distPath)) {
        http_response_code(503);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body><h1>Vue.js приложение не собрано</h1><p>Выполните: <code>cd frontend && npm run build</code></p></body></html>');
    }
    
    // Проверка авторизации Bitrix24
    if (!$authService->checkBitrix24Auth()) {
        // checkBitrix24Auth() уже выполнил редирект на failure.php
        exit;
    }
    
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
