<?php
/**
 * Файл инициализации сервисов
 *
 * Подключает все необходимые сервисы и хелперы
 * Использует b24phpsdk вместо CRest
 * Документация: https://context7.com/bitrix24/rest/
 */

// Автозагрузка Composer (b24phpsdk)
require_once(__DIR__ . '/../vendor/autoload.php');

// Подключение исключений
require_once(__DIR__ . '/Exceptions/Bitrix24ApiException.php');
require_once(__DIR__ . '/Exceptions/AccessDeniedException.php');
require_once(__DIR__ . '/Exceptions/ConfigException.php');

// Подключение клиентов
require_once(__DIR__ . '/Clients/ApiClientInterface.php');
require_once(__DIR__ . '/Clients/Bitrix24SdkClient.php'); // Новый SDK клиент

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

// Инициализация клиента API (новый SDK клиент)
$bitrix24Client = new App\Clients\Bitrix24SdkClient($logger);

// Инициализация с токеном установщика
// Пропускаем инициализацию во время установки (install.php), так как settings.json ещё не создан
$isInstallPage = basename($_SERVER['PHP_SELF'] ?? '') === 'install.php';
$isInstallEvent = isset($_REQUEST['event']) && $_REQUEST['event'] == 'ONAPPINSTALL';
$isPlacementEvent = isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] == 'DEFAULT';

// Инициализируем только если не происходит установка
if (!$isInstallPage || (!$isInstallEvent && !$isPlacementEvent)) {
    try {
        $bitrix24Client->initializeWithInstallerToken();
        $logger->log('SDK client initialized with installer token', [], 'info');
    } catch (\Exception $e) {
        $logger->logError('Failed to initialize SDK client', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        // В development режиме можно показать ошибку
        if (getenv('APP_ENV') === 'development' || defined('APP_DEBUG') && APP_DEBUG) {
            // В development можно выбросить исключение для отладки
            // throw $e;
        }
        // В production продолжаем работу (может быть установка не завершена)
    }
} else {
    // Во время установки не инициализируем SDK
    $logger->log('Skipping SDK initialization during installation', [
        'is_install_page' => $isInstallPage,
        'is_install_event' => $isInstallEvent,
        'is_placement_event' => $isPlacementEvent
    ], 'info');
}

// Инициализация сервисов с зависимостями
$apiService = new App\Services\Bitrix24ApiService($bitrix24Client, $logger);
$userService = new App\Services\UserService($apiService, $logger);
$accessControlService = new App\Services\AccessControlService($configService, $apiService, $userService, $logger);
$authService = new App\Services\AuthService($configService, $accessControlService, $apiService, $userService, $logger);

// Инициализация хелперов
$domainResolver = new App\Helpers\DomainResolver($configService);
$adminChecker = new App\Helpers\AdminChecker($apiService);

// Инициализация VueAppService
require_once(__DIR__ . '/Services/VueAppService.php');
$vueAppService = new App\Services\VueAppService($logger);

