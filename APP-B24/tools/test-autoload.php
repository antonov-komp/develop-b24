<?php
/**
 * Тестовый скрипт для проверки автозагрузки классов
 * 
 * Проверяет загрузку:
 * - Классов b24phpsdk
 * - Классов приложения
 * - Автозагрузку Composer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Проверка автозагрузки ===\n\n";

// 1. Проверка автозагрузки Composer
$autoloadFile = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    echo "✗ Файл vendor/autoload.php не найден\n";
    echo "  Выполните: composer install\n";
    exit(1);
}
require_once($autoloadFile);
echo "✓ Автозагрузка Composer подключена\n";

// 2. Проверка загрузки классов SDK
$sdkClasses = [
    'Bitrix24\SDK\Core\ApiClient',
    'Bitrix24\SDK\Core\Credentials\ApplicationProfile',
    'Bitrix24\SDK\Core\Credentials\Credentials',
    'Bitrix24\SDK\Core\Credentials\WebhookUrl',
    'Bitrix24\SDK\Core\Exceptions\BaseException'
];

echo "\n--- Проверка классов SDK ---\n";
$sdkOk = true;
foreach ($sdkClasses as $class) {
    if (class_exists($class) || interface_exists($class)) {
        echo "✓ {$class}\n";
    } else {
        echo "✗ {$class} - НЕ НАЙДЕН\n";
        $sdkOk = false;
    }
}

if (!$sdkOk) {
    echo "\n✗ Ошибка загрузки классов SDK\n";
    echo "  Проверьте установку: composer show bitrix24/b24phpsdk\n";
    exit(1);
}

// 3. Проверка загрузки классов приложения
echo "\n--- Проверка классов приложения ---\n";

// Подключаем bootstrap для загрузки классов приложения
require_once(__DIR__ . '/src/bootstrap.php');

$appClasses = [
    'App\Services\LoggerService',
    'App\Services\ConfigService',
    'App\Services\Bitrix24ApiService',
    'App\Clients\ApiClientInterface',
    'App\Clients\Bitrix24Client',
    'App\Services\UserService',
    'App\Services\AccessControlService',
    'App\Services\AuthService',
    'App\Helpers\DomainResolver',
    'App\Helpers\AdminChecker'
];

$appOk = true;
foreach ($appClasses as $class) {
    if (class_exists($class) || interface_exists($class)) {
        echo "✓ {$class}\n";
    } else {
        echo "✗ {$class} - НЕ НАЙДЕН\n";
        $appOk = false;
    }
}

if (!$appOk) {
    echo "\n✗ Ошибка загрузки классов приложения\n";
    exit(1);
}

// 4. Проверка версии SDK (если доступно)
echo "\n--- Информация о SDK ---\n";
try {
    // Попробуем получить информацию о пакете через Composer
    $composerLock = json_decode(file_get_contents(__DIR__ . '/composer.lock'), true);
    if ($composerLock && isset($composerLock['packages'])) {
        foreach ($composerLock['packages'] as $package) {
            if ($package['name'] === 'bitrix24/b24phpsdk') {
                echo "Пакет: {$package['name']}\n";
                echo "Версия: {$package['version']}\n";
                echo "Описание: " . ($package['description'] ?? 'N/A') . "\n";
                break;
            }
        }
    }
} catch (Exception $e) {
    echo "Версия SDK: информация недоступна ({$e->getMessage()})\n";
}

// 5. Проверка структуры vendor/
echo "\n--- Проверка структуры vendor/ ---\n";
$vendorSdkPath = __DIR__ . '/vendor/bitrix24/b24phpsdk';
if (is_dir($vendorSdkPath)) {
    echo "✓ Директория SDK найдена: {$vendorSdkPath}\n";
    
    $requiredDirs = ['src', 'tests'];
    foreach ($requiredDirs as $dir) {
        $dirPath = $vendorSdkPath . '/' . $dir;
        if (is_dir($dirPath)) {
            echo "✓ Директория {$dir} существует\n";
        } else {
            echo "✗ Директория {$dir} не найдена\n";
        }
    }
} else {
    echo "✗ Директория SDK не найдена: {$vendorSdkPath}\n";
    exit(1);
}

echo "\n=== Все проверки пройдены успешно ===\n";





