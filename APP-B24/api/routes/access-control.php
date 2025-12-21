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
        // Добавление отдела, пользователя или переключение enabled
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        if ($subRoute === 'toggle') {
            // Переключение включения/выключения проверки прав доступа
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
            
            // Валидация типа данных
            if (!is_bool($enabled) && !in_array($enabled, [0, 1, '0', '1', 'true', 'false'], true)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Bad request',
                    'message' => 'enabled must be a boolean value'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Нормализация значения
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
            
            $result = $accessControlService->toggleAccessControl(
                (bool)$enabled,
                [
                    'id' => $currentUser['ID'],
                    'name' => trim(($currentUser['NAME'] ?? '') . ' ' . ($currentUser['LAST_NAME'] ?? ''))
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
                    'error' => $result['error'] ?? 'Failed to toggle access control'
                ], JSON_UNESCAPED_UNICODE);
            }
        } elseif ($subRoute === 'departments') {
            $deptId = $input['department_id'] ?? null;
            $deptName = $input['department_name'] ?? null;
            
            // Детальная валидация
            $errors = [];
            
            if ($deptId === null) {
                $errors[] = 'department_id is required';
            } elseif (!is_numeric($deptId)) {
                $errors[] = 'department_id must be a number';
            } elseif ((int)$deptId <= 0) {
                $errors[] = 'department_id must be greater than 0';
            } elseif ((int)$deptId > PHP_INT_MAX) {
                $errors[] = 'department_id is too large';
            }
            
            if ($deptName === null) {
                $errors[] = 'department_name is required';
            } elseif (!is_string($deptName)) {
                $errors[] = 'department_name must be a string';
            } elseif (strlen(trim($deptName)) === 0) {
                $errors[] = 'department_name cannot be empty';
            } elseif (strlen($deptName) > 255) {
                $errors[] = 'department_name is too long (max 255 characters)';
            }
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errors
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
            
            // Детальная валидация
            $errors = [];
            
            if ($userId === null) {
                $errors[] = 'user_id is required';
            } elseif (!is_numeric($userId)) {
                $errors[] = 'user_id must be a number';
            } elseif ((int)$userId <= 0) {
                $errors[] = 'user_id must be greater than 0';
            } elseif ((int)$userId > PHP_INT_MAX) {
                $errors[] = 'user_id is too large';
            }
            
            if ($userName === null) {
                $errors[] = 'user_name is required';
            } elseif (!is_string($userName)) {
                $errors[] = 'user_name must be a string';
            } elseif (strlen(trim($userName)) === 0) {
                $errors[] = 'user_name cannot be empty';
            } elseif (strlen($userName) > 255) {
                $errors[] = 'user_name is too long (max 255 characters)';
            }
            
            if ($userEmail !== null && !empty($userEmail)) {
                if (!is_string($userEmail)) {
                    $errors[] = 'user_email must be a string';
                } elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'user_email must be a valid email address';
                } elseif (strlen($userEmail) > 255) {
                    $errors[] = 'user_email is too long (max 255 characters)';
                }
            }
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errors
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
            // Валидация ID
            if (!is_numeric($resourceId) || (int)$resourceId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid department ID'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $result = $accessControlService->removeDepartment((int)$resourceId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Department removed successfully'
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to remove department'
                ], JSON_UNESCAPED_UNICODE);
            }
        } elseif ($subRoute === 'users' && $resourceId) {
            // Валидация ID
            if (!is_numeric($resourceId) || (int)$resourceId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid user ID'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $result = $accessControlService->removeUser((int)$resourceId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User removed successfully'
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to remove user'
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

