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
    // Инициализация окружения
    require_once(__DIR__ . '/src/bootstrap.php');
    
    // Обработка запроса через основной сервис
    // $indexPageService инициализирован в bootstrap.php
    $indexPageService->handle();
    
} catch (\Throwable $e) {
    // Единая точка обработки критических ошибок
    // ВАЖНО: Это единственное место, где PHP генерирует HTML.
    // Используется только для критических ошибок, когда Vue.js не может загрузиться.
    // $errorHandlerService инициализирован в bootstrap.php
    $errorHandlerService->handleFatalError($e, $appEnv);
}
