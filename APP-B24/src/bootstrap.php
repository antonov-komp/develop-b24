<?php
/**
 * Файл инициализации сервисов
 * 
 * Подключает все необходимые сервисы и хелперы
 * Документация: https://context7.com/bitrix24/rest/
 */

// Подключение сервисов
require_once(__DIR__ . '/Services/LoggerService.php');
require_once(__DIR__ . '/Services/ConfigService.php');
require_once(__DIR__ . '/Services/Bitrix24ApiService.php');
require_once(__DIR__ . '/Services/UserService.php');
require_once(__DIR__ . '/Services/AccessControlService.php');
require_once(__DIR__ . '/Services/AuthService.php');

// Подключение хелперов
require_once(__DIR__ . '/Helpers/DomainResolver.php');
require_once(__DIR__ . '/Helpers/AdminChecker.php');

// Инициализация сервисов
$logger = new App\Services\LoggerService();
$configService = new App\Services\ConfigService($logger);
$apiService = new App\Services\Bitrix24ApiService($logger);
$userService = new App\Services\UserService($apiService, $logger);
$accessControlService = new App\Services\AccessControlService($configService, $apiService, $userService, $logger);
$authService = new App\Services\AuthService($configService, $accessControlService, $apiService, $userService, $logger);

// Инициализация хелперов
$domainResolver = new App\Helpers\DomainResolver($configService);
$adminChecker = new App\Helpers\AdminChecker($apiService);

