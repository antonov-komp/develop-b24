<?php
/**
 * API endpoint для анализа токена
 * 
 * Endpoints:
 * - GET - Анализ текущего токена
 * 
 * Параметры: AUTH_ID (или APP_SID), DOMAIN (query)
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

global $userService, $accessControlService, $configService, $logger, $apiService;

$authId = $auth['authId'];
$domain = $auth['domain'];
$method = $_SERVER['REQUEST_METHOD'];

// Проверка, что пользователь - администратор
try {
    $currentUser = $userService->getCurrentUser($authId, $domain);
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => 'Unable to get current user'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $isAdmin = $userService->isAdmin($currentUser, $authId, $domain);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Only administrators can analyze tokens'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Authorization check failed',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($method) {
    case 'GET':
        try {
            // Получение данных пользователя
            $user = $userService->getCurrentUser($authId, $domain);
            
            // Проверка статуса администратора
            $isAdmin = $userService->isAdmin($user, $authId, $domain);
            
            // Получение отделов
            $departments = $userService->getUserDepartments($user);
            
            // Проверка прав доступа
            $hasAccess = true;
            if (!$isAdmin) {
                $hasAccess = $accessControlService->checkUserAccess(
                    $user['ID'],
                    $departments,
                    $authId,
                    $domain
                );
            }
            
            // Получение конфигурации доступа
            $accessConfig = $configService->getAccessConfig();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'isAdmin' => $isAdmin,
                    'hasAccess' => $hasAccess,
                    'departments' => $departments,
                    'accessConfig' => $accessConfig,
                    'token' => [
                        'auth_id' => substr($authId, 0, 20) . '...',
                        'domain' => $domain
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to analyze token',
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

