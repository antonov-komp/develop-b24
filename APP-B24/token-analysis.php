<?php
/**
 * Страница анализа токена Bitrix24
 * 
 * Анализирует токен авторизации, его владельца и права доступа
 * Документация: https://context7.com/bitrix24/rest/
 */

// Подключение и инициализация сервисов
require_once(__DIR__ . '/src/bootstrap.php');

// Подключение контроллера
require_once(__DIR__ . '/src/Controllers/BaseController.php');
require_once(__DIR__ . '/src/Controllers/TokenAnalysisController.php');

// Создание контроллера
$controller = new App\Controllers\TokenAnalysisController(
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
