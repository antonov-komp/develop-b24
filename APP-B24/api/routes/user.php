<?php
/**
 * API для работы с пользователями
 * 
 * Endpoints:
 * - GET /api/user/current - Получение текущего пользователя
 * 
 * Параметры: AUTH_ID, DOMAIN (query или POST)
 * Документация: https://context7.com/bitrix24/rest/user.current
 */

require_once(__DIR__ . '/../middleware/auth.php');

// Проверка авторизации
$auth = checkApiAuth();
if (!$auth) {
    exit; // checkApiAuth уже отправил ответ
}

global $userService, $apiService, $logger;

$authId = $auth['authId'];
$domain = $auth['domain'];
$method = $_SERVER['REQUEST_METHOD'];
$segments = $GLOBALS['segments'] ?? [];
$subRoute = $segments[1] ?? null;

switch ($method) {
    case 'GET':
        if ($subRoute === 'current') {
            try {
                // Получение текущего пользователя
                $user = $userService->getCurrentUser($authId, $domain);
                
                if (!$user) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'User not found',
                        'message' => 'Unable to get current user'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // Проверка статуса администратора
                $isAdmin = $userService->isAdmin($user, $authId, $domain);
                
                // Получение отделов пользователя
                $departments = $userService->getUserDepartments($user);
                
                // Получение данных отделов
                $departmentData = [];
                if (!empty($departments)) {
                    foreach ($departments as $deptId) {
                        $dept = $apiService->getDepartment($deptId, $authId, $domain);
                        if ($dept) {
                            $departmentData[] = [
                                'id' => $dept['ID'],
                                'name' => $dept['NAME'] ?? 'Без названия'
                            ];
                        }
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'user' => $user,
                        'isAdmin' => $isAdmin,
                        'departments' => $departmentData
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to get user',
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Route not found',
                'message' => "Route 'user/{$subRoute}' not found"
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

