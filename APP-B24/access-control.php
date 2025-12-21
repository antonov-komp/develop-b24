<?php
/**
 * Страница управления правами доступа в приложении Bitrix24
 * 
 * ВАЖНО: PHP не генерирует UI. Вся визуальная часть на Vue.js.
 * PHP только:
 * - Проверяет авторизацию
 * - Загружает Vue.js приложение на маршрут /access-control
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */

// Определение окружения
$appEnv = getenv('APP_ENV') ?: 'production';

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
    
    // Проверка авторизации Bitrix24
    if (!$authService->checkBitrix24Auth()) {
        exit;
    }
    
    // Загрузка Vue.js приложения на маршрут /access-control
    loadVueApp('/access-control');
    
} catch (\Throwable $e) {
    error_log('Fatal error in access-control.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
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
