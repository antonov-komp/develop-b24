<?php
/**
 * API endpoint для управления правами доступа
 * 
 * Endpoints:
 * - GET - Получение конфигурации
 * - POST - Добавление/изменение конфигурации
 * - DELETE - Удаление элементов
 * 
 * Параметры: AUTH_ID (или APP_SID), DOMAIN, action (query или POST)
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

global $userService, $accessControlService, $configService, $logger;

$authId = $auth['authId'];
$domain = $auth['domain'];
$method = $_SERVER['REQUEST_METHOD'];

// Получаем action из query или POST
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $_GET['action'] ?? $input['action'] ?? null;

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
            'message' => 'Only administrators can manage access control'
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
        // Получение конфигурации прав доступа
        try {
            $accessConfig = $configService->getAccessConfig();
            
            echo json_encode([
                'success' => true,
                'data' => $accessConfig
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get access control config',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'POST':
        // Добавление отдела или пользователя, или переключение enabled
        if ($action === 'add-department') {
            $deptId = $input['departmentId'] ?? $input['department_id'] ?? null;
            $deptName = $input['departmentName'] ?? $input['department_name'] ?? null;
            
            if (!$deptId || !$deptName) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Bad request',
                    'message' => 'departmentId and departmentName are required'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $result = $accessControlService->addDepartment(
                (int)$deptId,
                $deptName,
                [
                    'id' => $currentUser['ID'],
                    'name' => ($currentUser['NAME'] ?? '') . ' ' . ($currentUser['LAST_NAME'] ?? '')
                ]
            );
            
            echo json_encode([
                'success' => $result['success'] ?? false,
                'data' => $result,
                'error' => $result['error'] ?? null
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } elseif ($action === 'add-user') {
            $userId = $input['userId'] ?? $input['user_id'] ?? null;
            $userName = $input['userName'] ?? $input['user_name'] ?? null;
            $userEmail = $input['userEmail'] ?? $input['user_email'] ?? null;
            
            if (!$userId || !$userName) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Bad request',
                    'message' => 'userId and userName are required'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $result = $accessControlService->addUser(
                (int)$userId,
                $userName,
                $userEmail,
                [
                    'id' => $currentUser['ID'],
                    'name' => ($currentUser['NAME'] ?? '') . ' ' . ($currentUser['LAST_NAME'] ?? '')
                ]
            );
            
            echo json_encode([
                'success' => $result['success'] ?? false,
                'data' => $result,
                'error' => $result['error'] ?? null
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } elseif ($action === 'toggle-enabled') {
            $enabled = $input['enabled'] ?? null;
            
            if ($enabled === null) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Bad request',
                    'message' => 'enabled parameter is required'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $accessConfig = $configService->getAccessConfig();
            $accessConfig['access_control']['enabled'] = (bool)$enabled;
            $configService->saveAccessConfig($accessConfig);
            
            echo json_encode([
                'success' => true,
                'data' => ['enabled' => (bool)$enabled]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Action not found',
                'message' => "Action '{$action}' not found for access-control endpoint"
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'DELETE':
        // Удаление отдела или пользователя
        $deleteAction = $input['action'] ?? $action;
        $resourceId = $input['departmentId'] ?? $input['userId'] ?? $input['department_id'] ?? $input['user_id'] ?? null;
        
        if ($deleteAction === 'remove-department' && $resourceId) {
            $result = $accessControlService->removeDepartment((int)$resourceId);
            
            echo json_encode([
                'success' => $result['success'] ?? false,
                'data' => $result,
                'error' => $result['error'] ?? null
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } elseif ($deleteAction === 'remove-user' && $resourceId) {
            $result = $accessControlService->removeUser((int)$resourceId);
            
            echo json_encode([
                'success' => $result['success'] ?? false,
                'data' => $result,
                'error' => $result['error'] ?? null
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Action or resource ID not found',
                'message' => "Action '{$deleteAction}' with resource ID not found"
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
