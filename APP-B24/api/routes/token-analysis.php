<?php
/**
 * API для анализа токена
 * 
 * Endpoints:
 * - GET /api/token-analysis - Анализ текущего токена
 * 
 * Параметры: AUTH_ID, DOMAIN (query или POST)
 */

require_once(__DIR__ . '/../middleware/auth.php');

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
            
            // Получение настроек из settings.json (Local)
            $localSettings = $configService->getSettings();
            
            // Безопасная обработка настроек (маскировка секретов)
            $safeLocalSettings = [
                'domain' => $localSettings['domain'] ?? null,
                'client_endpoint' => $localSettings['client_endpoint'] ?? null,
                'has_access_token' => !empty($localSettings['access_token']),
                'access_token_preview' => !empty($localSettings['access_token']) 
                    ? substr($localSettings['access_token'], 0, 20) . '...' 
                    : null,
                'has_refresh_token' => !empty($localSettings['refresh_token']),
                'refresh_token_preview' => !empty($localSettings['refresh_token']) 
                    ? substr($localSettings['refresh_token'], 0, 20) . '...' 
                    : null,
                'has_client_id' => !empty($localSettings['client_id']),
                'client_id_preview' => !empty($localSettings['client_id']) 
                    ? substr($localSettings['client_id'], 0, 20) . '...' 
                    : null,
                'has_client_secret' => !empty($localSettings['client_secret']),
                'has_application_token' => !empty($localSettings['application_token']),
                'application_token_preview' => !empty($localSettings['application_token']) 
                    ? substr($localSettings['application_token'], 0, 20) . '...' 
                    : null,
                'scope' => $localSettings['scope'] ?? null,
                'expires_in' => $localSettings['expires_in'] ?? null,
                'last_updated' => isset($localSettings['last_updated']) 
                    ? $localSettings['last_updated'] 
                    : (file_exists(__DIR__ . '/../../settings.json') 
                        ? date('Y-m-d H:i:s', filemtime(__DIR__ . '/../../settings.json')) 
                        : null)
            ];
            
            // Детальный анализ токена авторизации
            $refreshId = $_POST['REFRESH_ID'] ?? $_GET['REFRESH_ID'] ?? null;
            $authExpires = isset($_POST['AUTH_EXPIRES']) 
                ? (int)$_POST['AUTH_EXPIRES'] 
                : (isset($_GET['AUTH_EXPIRES']) ? (int)$_GET['AUTH_EXPIRES'] : null);
            
            $tokenAnalysis = [
                'type' => 'user_token', // Тип токена: user_token или installer_token
                'auth_id_length' => strlen($authId),
                'auth_id_preview' => substr($authId, 0, 20) . '...' . substr($authId, -10),
                'has_refresh_token' => !empty($refreshId),
                'refresh_token_length' => $refreshId ? strlen($refreshId) : null,
                'refresh_token_preview' => $refreshId 
                    ? substr($refreshId, 0, 20) . '...' . substr($refreshId, -10) 
                    : null,
                'expires_at' => $authExpires ? date('Y-m-d H:i:s', $authExpires) : null,
                'expires_timestamp' => $authExpires,
                'is_expired' => $authExpires ? ($authExpires < time()) : null,
                'time_until_expiry' => $authExpires 
                    ? ($authExpires > time() ? ($authExpires - time()) : 0) 
                    : null,
                'time_until_expiry_formatted' => $authExpires && $authExpires > time()
                    ? gmdate('H:i:s', $authExpires - time())
                    : null,
                'domain' => $domain,
                'domain_region' => detectDomainRegion($domain)
            ];
            
            // Проверка прав доступа (scope) и доступных методов
            $permissions = checkPermissions($apiService, $authId, $domain, $logger);
            
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
                    ],
                    'tokenAnalysis' => $tokenAnalysis,
                    'localSettings' => $safeLocalSettings,
                    'permissions' => $permissions
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

/**
 * Проверка прав доступа и доступных методов API
 * 
 * @param \App\Services\Bitrix24ApiService $apiService Сервис для работы с API
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @param \App\Services\LoggerService $logger Сервис логирования
 * @return array Информация о правах доступа
 */
function checkPermissions($apiService, string $authId, string $domain, $logger): array
{
    $permissions = [
        'current_scope' => null,
        'all_available_scope' => null,
        'available_methods_count' => null,
        'tested_methods' => []
    ];
    
    try {
        // Получение текущих прав доступа (scope)
        $currentScope = $apiService->getCurrentScope($authId, $domain);
        if ($currentScope) {
            $permissions['current_scope'] = is_array($currentScope) ? $currentScope : [$currentScope];
        }
        
        // Получение всех возможных прав доступа
        $allScope = $apiService->getAllAvailableScope($authId, $domain);
        if ($allScope) {
            $permissions['all_available_scope'] = is_array($allScope) ? $allScope : [$allScope];
        }
        
        // Получение списка доступных методов
        $availableMethods = $apiService->getAvailableMethods($authId, $domain);
        if ($availableMethods) {
            $permissions['available_methods_count'] = is_array($availableMethods) ? count($availableMethods) : 0;
            $permissions['available_methods_preview'] = is_array($availableMethods) 
                ? array_slice($availableMethods, 0, 20) 
                : [];
        }
        
        // Проверка доступности ключевых методов
        $keyMethods = [
            'user.current',
            'user.get',
            'user.admin',
            'user.access',
            'department.get',
            'crm.lead.list',
            'crm.deal.list',
            'crm.contact.list',
            'scope',
            'method.get',
            'methods'
        ];
        
        $testedMethods = $apiService->checkMultipleMethodsAvailability($keyMethods, $authId, $domain);
        $permissions['tested_methods'] = $testedMethods;
        
        $logger->log('Permissions check completed', [
            'has_current_scope' => !empty($permissions['current_scope']),
            'scope_count' => is_array($permissions['current_scope']) ? count($permissions['current_scope']) : 0,
            'available_methods_count' => $permissions['available_methods_count'],
            'tested_methods_count' => count($permissions['tested_methods'])
        ], 'info');
        
    } catch (\Exception $e) {
        $logger->logError('Error checking permissions', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $permissions['error'] = $e->getMessage();
    }
    
    return $permissions;
}

/**
 * Определение региона домена Bitrix24
 * 
 * @param string $domain Домен портала
 * @return string|null Регион (ru, eu, us, cn) или null
 */
function detectDomainRegion(string $domain): ?string
{
    if (empty($domain)) {
        return null;
    }
    
    // Убираем протокол
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = rtrim($domain, '/');
    
    // Определение региона по домену
    if (strpos($domain, '.bitrix24.ru') !== false || strpos($domain, '.bitrix24.com') !== false) {
        // Проверяем поддомен для определения региона
        if (strpos($domain, '.eu') !== false) {
            return 'eu';
        } elseif (strpos($domain, '.us') !== false) {
            return 'us';
        } elseif (strpos($domain, '.cn') !== false) {
            return 'cn';
        } else {
            return 'ru'; // По умолчанию для .ru доменов
        }
    }
    
    return null;
}

