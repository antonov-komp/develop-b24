<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только внутри Bitrix24 при активной авторизации
 * Управляется через конфигурационный файл config.json
 * Отображает приветствие с информацией о текущем пользователе
 * Документация: https://context7.com/bitrix24/rest/
 */

// Определение окружения (development/production)
// По умолчанию production для безопасности
$appEnv = getenv('APP_ENV') ?: 'production';

// Условное включение отладочных настроек
if ($appEnv === 'development') {
    // Режим разработки - показываем все ошибки
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Режим продакшена - скрываем ошибки, но логируем их
    error_reporting(E_ALL); // Логируем все ошибки
    ini_set('display_errors', 0); // Не показываем на экране
    ini_set('log_errors', 1); // Логируем в файлы
    ini_set('error_log', __DIR__ . '/logs/php-errors.log'); // Путь к логу ошибок
}

try {
    // Подключение и инициализация сервисов
    require_once(__DIR__ . '/src/bootstrap.php');

    // Подключение контроллера
    require_once(__DIR__ . '/src/Controllers/BaseController.php');
    require_once(__DIR__ . '/src/Controllers/IndexController.php');

    // Создание контроллера
    $controller = new App\Controllers\IndexController(
        $logger,
        $configService,
        $apiService,
        $userService,
        $accessControlService,
        $authService,
        $domainResolver,
        $adminChecker
    );

    // Вызов метода контроллера
    $controller->index();
} catch (\Throwable $e) {
    // Логирование ошибки
    error_log('Fatal error in index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    // Показываем ошибку (в продакшене лучше показывать общую страницу ошибки)
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
