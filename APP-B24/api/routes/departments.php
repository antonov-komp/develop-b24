<?php
/**
 * API для работы с отделами
 * 
 * Endpoints:
 * - GET /api/departments - Получение списка всех отделов
 * 
 * Параметры: AUTH_ID, DOMAIN (query или POST)
 * Документация: https://context7.com/bitrix24/rest/department.get
 */

require_once(__DIR__ . '/../middleware/auth.php');

// Проверка авторизации
$auth = checkApiAuth();
if (!$auth) {
    exit; // checkApiAuth уже отправил ответ
}

global $apiService, $logger;

$authId = $auth['authId'];
$domain = $auth['domain'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // Получение всех отделов
            $departments = $apiService->getAllDepartments($authId, $domain);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'departments' => $departments
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get departments',
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

