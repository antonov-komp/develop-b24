<?php
/**
 * API для управления правами доступа
 * 
 * Endpoints:
 * - GET /api/access-control - Получение конфигурации прав доступа
 * - POST /api/access-control/departments - Добавление отдела
 * - POST /api/access-control/users - Добавление пользователя
 * - DELETE /api/access-control/departments/{id} - Удаление отдела
 * - DELETE /api/access-control/users/{id} - Удаление пользователя
 * 
 * Параметры: AUTH_ID, DOMAIN (query или POST)
 */

require_once(__DIR__ . '/../middleware/auth.php');

// Проверка авторизации
$auth = checkApiAuth();
if (!$auth) {
    exit; // checkApiAuth уже отправил ответ
}

global $userService, $accessControlService, $configService, $logger;

$authId = $auth['authId'];
$domain = $auth['domain'];
$method = $_SERVER['REQUEST_METHOD'];
$segments = $GLOBALS['segments'] ?? [];
$subRoute = $segments[1] ?? null;
$resourceId = $segments[2] ?? null;

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
        // Добавление отдела или пользователя
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        if ($subRoute === 'departments') {
            $deptId = $input['department_id'] ?? null;
            $deptName = $input['department_name'] ?? null;
            
            if (!$deptId || !$deptName) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Bad request',
                    'message' => 'department_id and department_name are required'
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
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to add department'
                ], JSON_UNESCAPED_UNICODE);
            }
        } elseif ($subRoute === 'users') {
            $userId = $input['user_id'] ?? null;
            $userName = $input['user_name'] ?? null;
            $userEmail = $input['user_email'] ?? null;
            
            if (!$userId || !$userName) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Bad request',
                    'message' => 'user_id and user_name are required'
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
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to add user'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Route not found',
                'message' => "Route 'access-control/{$subRoute}' not found"
            ], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    case 'DELETE':
        // Удаление отдела или пользователя
        if ($subRoute === 'departments' && $resourceId) {
            $result = $accessControlService->removeDepartment((int)$resourceId);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to remove department'
                ], JSON_UNESCAPED_UNICODE);
            }
        } elseif ($subRoute === 'users' && $resourceId) {
            $result = $accessControlService->removeUser((int)$resourceId);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to remove user'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Route not found',
                'message' => "Route 'access-control/{$subRoute}/{$resourceId}' not found"
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

