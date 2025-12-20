<?php
/**
 * Страница установки приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только при установке из Bitrix24
 * Использует AuthService для проверки авторизации
 * Документация: https://context7.com/bitrix24/rest/
 */

// Включение отображения ошибок для отладки (убрать в продакшене)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Подключение и инициализация сервисов
    require_once(__DIR__ . '/src/bootstrap.php');
    
    // Проверка авторизации Bitrix24 через AuthService
    // Для install.php разрешаем доступ только если есть параметры установки от Bitrix24
    if (!$authService->checkBitrix24Auth()) {
        $authService->redirectToFailure();
        exit;
    }
    
    // Подключение библиотеки CRest для установки
    require_once(__DIR__ . '/crest.php');
    
    // Выполнение установки приложения
    $result = CRest::installApp();
    
    // Обработка ошибок установки
    $error = null;
    if (isset($result['install']) && $result['install'] === false) {
        $error = 'Не удалось установить приложение. Проверьте параметры установки.';
        
        // Логирование ошибки установки
        if (isset($logger)) {
            $logger->logError('Installation failed', [
                'result' => $result,
                'request_params' => array_intersect_key($_REQUEST, array_flip(['DOMAIN', 'AUTH_ID', 'APP_SID', 'PLACEMENT', 'event'])),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    // Если это не только REST API - отображаем шаблон
    if (!isset($result['rest_only']) || $result['rest_only'] === false) {
        // Передача данных в шаблон
        include(__DIR__ . '/templates/install.php');
    } else {
        // Для REST-only режима - просто завершаем выполнение
        // (установка уже выполнена через CRest)
    }
    
} catch (\Exception $e) {
    // Обработка исключений
    $error = 'Произошла ошибка при установке: ' . $e->getMessage();
    
    // Логирование исключения
    if (isset($logger)) {
        $logger->logError('Installation exception', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Отображение шаблона с ошибкой
    $result = [
        'install' => false,
        'rest_only' => false
    ];
    include(__DIR__ . '/templates/install.php');
}
