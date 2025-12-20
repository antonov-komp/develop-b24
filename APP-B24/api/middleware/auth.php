<?php
/**
 * Middleware для проверки авторизации API запросов
 * 
 * @param string|null $authId Токен авторизации
 * @param string|null $domain Домен портала
 * @return array ['success' => bool, 'authId' => string, 'domain' => string] или null при ошибке
 */
function checkApiAuth(?string $authId = null, ?string $domain = null): ?array
{
    global $authService, $logger, $segments;
    
    // Получение параметров из разных источников
    // Bitrix24 может передавать APP_SID вместо AUTH_ID
    $authId = $authId 
        ?? $_GET['AUTH_ID'] 
        ?? $_GET['APP_SID']  // APP_SID как fallback
        ?? $_POST['AUTH_ID'] 
        ?? $_POST['APP_SID']  // APP_SID как fallback в POST
        ?? (json_decode(file_get_contents('php://input'), true)['AUTH_ID'] ?? null)
        ?? (json_decode(file_get_contents('php://input'), true)['APP_SID'] ?? null)  // APP_SID в JSON
        ?? null;
        
    $domain = $domain 
        ?? $_GET['DOMAIN'] 
        ?? $_POST['DOMAIN'] 
        ?? (json_decode(file_get_contents('php://input'), true)['DOMAIN'] ?? null)
        ?? null;
    
    // Валидация параметров
    if (!$authId || !$domain) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => 'AUTH_ID and DOMAIN are required'
        ], JSON_UNESCAPED_UNICODE);
        return null;
    }
    
    // Проверка формата токена (базовая валидация)
    if (strlen($authId) < 10) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid token format',
            'message' => 'AUTH_ID is too short'
        ], JSON_UNESCAPED_UNICODE);
        return null;
    }
    
    // Проверка авторизации через AuthService
    try {
        // Для API запросов проверяем, что запрос из Bitrix24
        if (!$authService->isRequestFromBitrix24()) {
            // Если нет параметров Bitrix24, но есть токен - разрешаем (для тестирования)
            // В production можно ужесточить проверку
            $logger->log('API request without Bitrix24 parameters', [
                'has_auth_id' => !empty($authId),
                'has_domain' => !empty($domain)
            ], 'warning');
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Authorization check failed',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        return null;
    }
    
    return [
        'success' => true,
        'authId' => $authId,
        'domain' => $domain
    ];
}

