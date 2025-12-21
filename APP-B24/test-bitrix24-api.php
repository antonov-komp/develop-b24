<?php
/**
 * Тестовый скрипт для проверки работы Bitrix24 API
 * 
 * Использование:
 * php test-bitrix24-api.php AUTH_ID DOMAIN
 * 
 * Пример:
 * php test-bitrix24-api.php 0e068a8c37f8c759c8b2e1c79424da0e develop.bitrix24.by
 */

require_once(__DIR__ . '/src/bootstrap.php');

$authId = $argv[1] ?? '0e068a8c37f8c759c8b2e1c79424da0e';
$domain = $argv[2] ?? 'develop.bitrix24.by';

echo "=== Тест Bitrix24 API ===\n\n";
echo "AUTH_ID: " . substr($authId, 0, 10) . "...\n";
echo "DOMAIN: {$domain}\n\n";

try {
    require_once(__DIR__ . '/src/Services/LoggerService.php');
    require_once(__DIR__ . '/src/Clients/Bitrix24SdkClient.php');
    
    $logger = new \App\Services\LoggerService();
    $client = new \App\Clients\Bitrix24SdkClient($logger);
    
    echo "Инициализация с токеном пользователя...\n";
    $client->initializeWithUserToken($authId, $domain);
    echo "✅ Инициализация успешна\n\n";
    
    echo "Вызов user.current...\n";
    $result = $client->call('user.current', []);
    
    echo "\n=== Результат ===\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if (isset($result['error'])) {
        echo "❌ Ошибка API:\n";
        echo "  Error: " . $result['error'] . "\n";
        echo "  Description: " . ($result['error_description'] ?? 'No description') . "\n";
    } elseif (isset($result['result'])) {
        echo "✅ Пользователь найден:\n";
        echo "  ID: " . ($result['result']['ID'] ?? 'N/A') . "\n";
        echo "  Name: " . ($result['result']['NAME'] ?? 'N/A') . "\n";
        echo "  Last Name: " . ($result['result']['LAST_NAME'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Результат пустой или неожиданный формат\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Исключение:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "\n  Trace:\n" . $e->getTraceAsString() . "\n";
}
