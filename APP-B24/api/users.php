<?php
/**
 * API endpoint для работы с пользователями
 * 
 * Endpoints:
 * - GET - Получение списка всех пользователей
 * 
 * Параметры: AUTH_ID (или APP_SID), DOMAIN (query)
 * Опционально: search - поисковый запрос (query)
 * 
 * Документация: https://context7.com/bitrix24/rest/user.get
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/../src/bootstrap.php');
require_once(__DIR__ . '/middleware/auth.php');

// Проверка авторизации
$auth = checkApiAuth();
if (!$auth) {
    exit; // checkApiAuth уже отправил ответ
}

global $apiService, $logger;

$authId = $auth['authId'];
$domain = $auth['domain'];
$method = $_SERVER['REQUEST_METHOD'];
$search = $_GET['search'] ?? null;

switch ($method) {
    case 'GET':
        try {
            // Получение всех пользователей
            $users = $apiService->getAllUsers($authId, $domain, $search);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'users' => $users
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get users',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'message' => "Method {$method} is not supported for this endpoint"
        ], JSON_UNESCAPED_UNICODE);
        break;
}

