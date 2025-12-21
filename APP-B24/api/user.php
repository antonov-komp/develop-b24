<?php
/**
 * API endpoint для работы с пользователями
 * 
 * Endpoints:
 * - GET ?action=current - Получение текущего пользователя
 * 
 * Параметры: AUTH_ID (или APP_SID), DOMAIN, action (query)
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

global $userService, $apiService, $logger;

$authId = $auth['authId'];
$domain = $auth['domain'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

switch ($method) {
    case 'GET':
        if ($action === 'current') {
            try {
                // Логирование всех параметров запроса для диагностики SDK
                $logger->log('API user.php: Starting getCurrentUser request', [
                    'auth_id_length' => strlen($authId),
                    'domain' => $domain,
                    'action' => $action,
                    'method' => $method,
                    'request_params' => [
                        'AUTH_ID' => isset($_REQUEST['AUTH_ID']) ? substr($_REQUEST['AUTH_ID'], 0, 10) . '...' : 'not set',
                        'APP_SID' => isset($_REQUEST['APP_SID']) ? substr($_REQUEST['APP_SID'], 0, 10) . '...' : 'not set',
                        'REFRESH_ID' => isset($_REQUEST['REFRESH_ID']) ? substr($_REQUEST['REFRESH_ID'], 0, 10) . '...' : 'not set',
                        'AUTH_EXPIRES' => $_REQUEST['AUTH_EXPIRES'] ?? 'not set',
                        'DOMAIN' => $_REQUEST['DOMAIN'] ?? 'not set',
                        'all_get_params' => array_keys($_GET),
                        'all_post_params' => array_keys($_POST),
                        'all_request_params' => array_keys($_REQUEST)
                    ]
                ], 'info');
                
                // Получение текущего пользователя
                $user = $userService->getCurrentUser($authId, $domain);
                
                if (!$user) {
                    // Логирование ошибки с деталями
                    $logger->logError('API user.php: User not found', [
                        'auth_id_length' => strlen($authId),
                        'domain' => $domain,
                        'auth_id_preview' => substr($authId, 0, 10) . '...',
                        'possible_reasons' => [
                            'Invalid or expired AUTH_ID token',
                            'Token does not match domain',
                            'Bitrix24 API returned error',
                            'User does not exist in Bitrix24'
                        ]
                    ]);
                    
                    // Возвращаем 200 с детальной ошибкой вместо 404 для лучшей диагностики
                    http_response_code(200);
                    echo json_encode([
                        'success' => false,
                        'error' => 'User not found',
                        'message' => 'Unable to get current user from Bitrix24',
                        'debug' => [
                            'auth_id_length' => strlen($authId),
                            'domain' => $domain,
                            'auth_id_preview' => substr($authId, 0, 10) . '...',
                            'timestamp' => date('Y-m-d H:i:s')
                        ],
                        'possible_reasons' => [
                            'Invalid or expired AUTH_ID token',
                            'Token does not match domain',
                            'Bitrix24 API returned error',
                            'User does not exist in Bitrix24'
                        ],
                        'suggestions' => [
                            'Check if AUTH_ID token is valid and not expired',
                            'Verify that token matches the domain',
                            'Check Bitrix24 API logs for errors',
                            'Verify user exists in Bitrix24 portal'
                        ]
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    exit;
                }
                
                // Логирование успешного получения пользователя
                $logger->log('API user.php: User found successfully', [
                    'user_id' => $user['ID'] ?? null,
                    'user_name' => ($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? ''),
                    'domain' => $domain
                ], 'info');
                
                // Проверка статуса администратора
                try {
                    $isAdmin = $userService->isAdmin($user, $authId, $domain);
                } catch (\Exception $e) {
                    $logger->logError('API user.php: Error checking admin status', [
                        'user_id' => $user['ID'] ?? null,
                        'error' => $e->getMessage()
                    ]);
                    $isAdmin = false; // По умолчанию не администратор при ошибке
                }
                
                // Получение отделов пользователя
                $departments = [];
                $departmentData = [];
                try {
                    $departments = $userService->getUserDepartments($user);
                    
                    // Получение данных отделов
                    if (!empty($departments)) {
                        foreach ($departments as $deptId) {
                            try {
                                $dept = $apiService->getDepartment($deptId, $authId, $domain);
                                if ($dept) {
                                    $departmentData[] = [
                                        'id' => $dept['ID'],
                                        'name' => $dept['NAME'] ?? 'Без названия'
                                    ];
                                }
                            } catch (\Exception $e) {
                                $logger->logError('API user.php: Error getting department', [
                                    'department_id' => $deptId,
                                    'error' => $e->getMessage()
                                ]);
                                // Продолжаем работу, даже если отдел не найден
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $logger->logError('API user.php: Error getting user departments', [
                        'user_id' => $user['ID'] ?? null,
                        'error' => $e->getMessage()
                    ]);
                    // Продолжаем работу без отделов
                }
                
                // Успешный ответ
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'user' => $user,
                        'isAdmin' => $isAdmin,
                        'departments' => $departmentData
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                
                // Логирование успешного завершения
                $logger->log('API user.php: Request completed successfully', [
                    'user_id' => $user['ID'] ?? null,
                    'is_admin' => $isAdmin,
                    'departments_count' => count($departmentData)
                ], 'info');
                
            } catch (\Exception $e) {
                // Детальное логирование исключения
                $logger->logError('API user.php: Exception occurred', [
                    'exception_message' => $e->getMessage(),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                    'exception_trace' => $e->getTraceAsString(),
                    'auth_id_length' => strlen($authId),
                    'domain' => $domain
                ]);
                
                // Возвращаем детальную ошибку
                http_response_code(500);
                $isDevelopment = getenv('APP_ENV') === 'development';
                echo json_encode([
                    'success' => false,
                    'error' => 'Internal server error',
                    'message' => 'An error occurred while processing the request',
                    'debug' => $isDevelopment ? [
                        'exception_message' => $e->getMessage(),
                        'exception_file' => $e->getFile(),
                        'exception_line' => $e->getLine(),
                        'timestamp' => date('Y-m-d H:i:s')
                    ] : null
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        } else {
            // Неизвестное действие
            $logger->logError('API user.php: Unknown action', [
                'action' => $action,
                'available_actions' => ['current']
            ]);
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action',
                'message' => "Action '{$action}' is not supported",
                'available_actions' => ['current']
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

