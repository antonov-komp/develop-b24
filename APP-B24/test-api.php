<?php
/**
 * Тестовый скрипт для проверки API endpoints
 * 
 * Использование:
 * php test-api.php
 * 
 * Или через браузер с параметрами:
 * test-api.php?AUTH_ID=...&DOMAIN=...
 */

require_once(__DIR__ . '/src/bootstrap.php');

// Получение параметров
$authId = $_GET['AUTH_ID'] ?? null;
$domain = $_GET['DOMAIN'] ?? null;

if (!$authId || !$domain) {
    die("Использование: test-api.php?AUTH_ID=...&DOMAIN=...\n");
}

echo "=== Тестирование API endpoints ===\n\n";

// Тест 1: Получение текущего пользователя
echo "1. Тест: GET /api/user/current\n";
$url = "http://localhost/APP-B24/api/user/current?AUTH_ID=" . urlencode($authId) . "&DOMAIN=" . urlencode($domain);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "   ✓ Успешно\n";
    echo "   Пользователь: " . ($data['data']['user']['NAME'] ?? 'N/A') . "\n";
    echo "   Администратор: " . ($data['data']['isAdmin'] ? 'Да' : 'Нет') . "\n";
} else {
    echo "   ✗ Ошибка: " . ($data['message'] ?? $response) . "\n";
}
echo "\n";

// Тест 2: Получение отделов
echo "2. Тест: GET /api/departments\n";
$url = "http://localhost/APP-B24/api/departments?AUTH_ID=" . urlencode($authId) . "&DOMAIN=" . urlencode($domain);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "   ✓ Успешно\n";
    $count = count($data['data']['departments'] ?? []);
    echo "   Отделов: $count\n";
} else {
    echo "   ✗ Ошибка: " . ($data['message'] ?? $response) . "\n";
}
echo "\n";

// Тест 3: Анализ токена (только для администраторов)
echo "3. Тест: GET /api/token-analysis\n";
$url = "http://localhost/APP-B24/api/token-analysis?AUTH_ID=" . urlencode($authId) . "&DOMAIN=" . urlencode($domain);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "   ✓ Успешно\n";
    echo "   Администратор: " . ($data['data']['isAdmin'] ? 'Да' : 'Нет') . "\n";
    echo "   Имеет доступ: " . ($data['data']['hasAccess'] ? 'Да' : 'Нет') . "\n";
} else {
    echo "   ✗ Ошибка: " . ($data['message'] ?? $response) . "\n";
}
echo "\n";

echo "=== Тестирование завершено ===\n";


