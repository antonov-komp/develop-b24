<?php
/**
 * Проверка сервера для работы с Bitrix24
 * 
 * Использует b24phpsdk для проверки подключения
 * Документация: https://github.com/bitrix24/b24phpsdk
 */

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../src/Services/LoggerService.php');
require_once(__DIR__ . '/../src/Clients/Bitrix24SdkClient.php');

use App\Services\LoggerService;
use App\Clients\Bitrix24SdkClient;

$logger = new LoggerService();
$client = new Bitrix24SdkClient($logger);

try {
    $client->initializeWithInstallerToken();
    
    // Проверка подключения через простой вызов API
    $result = $client->call('app.info');
    
    echo "✓ Сервер настроен корректно\n";
    echo "✓ Подключение к Bitrix24 работает\n";
    echo "✓ Приложение: " . ($result['result']['NAME'] ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "✗ Ошибка подключения: " . $e->getMessage() . "\n";
    exit(1);
}

