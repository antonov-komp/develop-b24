<?php
/**
 * Страница управления правами доступа в приложении Bitrix24
 * 
 * Позволяет администраторам настраивать, кто имеет доступ к приложению
 * Документация: https://context7.com/bitrix24/rest/
 */

// Подключение и инициализация сервисов
require_once(__DIR__ . '/src/bootstrap.php');

// Подключение контроллера
require_once(__DIR__ . '/src/Controllers/BaseController.php');
require_once(__DIR__ . '/src/Controllers/AccessControlController.php');

// Создание контроллера
$controller = new App\Controllers\AccessControlController(
    $logger,
    $configService,
    $apiService,
    $userService,
    $accessControlService,
    $authService,
    $domainResolver
);

// Вызов метода контроллера
$controller->index();
