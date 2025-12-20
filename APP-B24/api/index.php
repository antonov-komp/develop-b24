<?php
/**
 * REST API для Vue.js фронтенда
 * 
 * Все запросы проходят через этот файл
 * Документация: /DOCS/REFACTOR/migration-plan.md
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

// Получение окружения
$appEnv = getenv('APP_ENV') ?: 'production';

// Получение маршрута
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Удаляем префикс /APP-B24/api если есть
$path = preg_replace('#^/APP-B24/api#', '', $path);
$path = preg_replace('#^/api#', '', $path);
$segments = array_filter(explode('/', trim($path, '/')));
$segments = array_values($segments);

// Маршрутизация
$route = $segments[0] ?? 'index';
$subRoute = $segments[1] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// Логирование запроса (для отладки)
if ($appEnv === 'development') {
    $logger->log('API Request', [
        'method' => $method,
        'route' => $route,
        'sub_route' => $subRoute,
        'path' => $path,
        'segments' => $segments
    ], 'info');
}

try {
    switch ($route) {
        case 'user':
            require_once(__DIR__ . '/routes/user.php');
            break;
        case 'departments':
            require_once(__DIR__ . '/routes/departments.php');
            break;
        case 'access-control':
            require_once(__DIR__ . '/routes/access-control.php');
            break;
        case 'token-analysis':
            require_once(__DIR__ . '/routes/token-analysis.php');
            break;
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Route not found',
                'message' => "Route '{$route}' not found"
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (\Exception $e) {
    http_response_code(500);
    
    // Логирование ошибки
    $logger->logError('API Error', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $appEnv === 'development' ? $e->getMessage() : 'An error occurred',
        'trace' => $appEnv === 'development' ? $e->getTraceAsString() : null,
        'file' => $appEnv === 'development' ? $e->getFile() : null,
        'line' => $appEnv === 'development' ? $e->getLine() : null
    ], JSON_UNESCAPED_UNICODE);
}

