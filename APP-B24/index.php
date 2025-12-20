<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только внутри Bitrix24 при активной авторизации
 * Управляется через конфигурационный файл config.json
 * Отображает приветствие с информацией о текущем пользователе
 * Документация: https://context7.com/bitrix24/rest/
 */

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
