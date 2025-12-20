<?php

namespace App\Services;

require_once(__DIR__ . '/../../crest.php');

/**
 * Сервис для проверки авторизации Bitrix24
 * 
 * Проверяет, что запрос приходит из Bitrix24 с активной авторизацией
 * Используется для защиты index.php и install.php от прямого доступа
 * Документация: https://context7.com/bitrix24/rest/
 */
class AuthService
{
    protected ConfigService $configService;
    protected AccessControlService $accessControlService;
    protected Bitrix24ApiService $apiService;
    protected UserService $userService;
    protected LoggerService $logger;
    
    public function __construct(
        ConfigService $configService,
        AccessControlService $accessControlService,
        Bitrix24ApiService $apiService,
        UserService $userService,
        LoggerService $logger
    ) {
        $this->configService = $configService;
        $this->accessControlService = $accessControlService;
        $this->apiService = $apiService;
        $this->userService = $userService;
        $this->logger = $logger;
    }
    
    /**
     * Проверка авторизации Bitrix24
     * 
     * @return bool true если авторизация валидна
     */
    public function checkBitrix24Auth(): bool
    {
        // Логирование для диагностики
        $logData = [
            'script' => basename($_SERVER['PHP_SELF']),
            'request_params' => array_intersect_key($_REQUEST, array_flip(['DOMAIN', 'AUTH_ID', 'APP_SID', 'PLACEMENT', 'event'])),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'not_set',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 100) : 'not_set',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Для install.php - разрешаем доступ только если есть параметры установки от Bitrix24
        if (basename($_SERVER['PHP_SELF']) === 'install.php') {
            // Проверяем наличие параметров установки от Bitrix24
            if (
                (isset($_REQUEST['event']) && $_REQUEST['event'] === 'ONAPPINSTALL' && !empty($_REQUEST['auth'])) ||
                (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'DEFAULT') ||
                (isset($_REQUEST['AUTH_ID']) && isset($_REQUEST['DOMAIN']))
            ) {
                // Это запрос от Bitrix24 для установки - разрешаем
                return true;
            }
            // Если нет параметров установки - доступ запрещён
            return false;
        }
        
        // Для index.php и других файлов - проверяем, что запрос приходит ИЗ Bitrix24
        $isFromBitrix24 = $this->isRequestFromBitrix24();
        $logData['is_from_bitrix24'] = $isFromBitrix24;
        
        // Проверяем наличие установленного приложения
        $settings = $this->configService->getSettings();
        
        if (empty($settings['access_token']) || empty($settings['domain']) || empty($settings['client_endpoint'])) {
            // Настройки не найдены или неполные - приложение не установлено
            return false;
        }
        
        // Проверяем валидность токена через тестовый запрос к Bitrix24
        try {
            $testResult = $this->apiService->call('profile');
            
            // Если есть ошибка авторизации - проверяем тип ошибки
            if (isset($testResult['error'])) {
                // Исключение: expired_token - токен можно обновить автоматически
                if ($testResult['error'] === 'expired_token') {
                    // Bitrix24Client автоматически обновит токен при следующем запросе
                    // Проверяем ещё раз после обновления
                    $refreshResult = $this->apiService->call('profile');
                    if (isset($refreshResult['error']) && 
                        in_array($refreshResult['error'], ['invalid_token', 'invalid_grant', 'invalid_client', 'NO_AUTH_FOUND'])) {
                        $logData['result'] = 'denied_token_invalid_after_refresh';
                        $logData['error'] = $refreshResult['error'];
                        $this->logger->logAuthCheck('Auth check failed', $logData);
                        return false;
                    }
                    // Токен обновлён успешно - проверяем источник запроса
                    if (!$isFromBitrix24) {
                        // Если нет Referer и нет параметров - это прямой доступ
                        if (!isset($_SERVER['HTTP_REFERER']) && 
                            empty($_REQUEST['DOMAIN']) && 
                            empty($_REQUEST['AUTH_ID']) &&
                            empty($_REQUEST['APP_SID'])) {
                            $logData['result'] = 'denied_direct_access_no_signs';
                            $logData['is_from_bitrix24'] = false;
                            $this->logger->logAuthCheck('Auth check failed', $logData);
                            return false;
                        }
                    }
                    $logData['result'] = 'allowed_token_refreshed';
                    $logData['is_from_bitrix24'] = $isFromBitrix24;
                    $this->logger->logAuthCheck('Auth check passed', $logData);
                    return true;
                }
                
                // Ошибка no_install_app - возможно, settings.json повреждён или содержит тестовые данные
                // Если есть признаки запроса из Bitrix24 - разрешаем доступ (для работы через iframe)
                if ($testResult['error'] === 'no_install_app' && $isFromBitrix24) {
                    $logData['result'] = 'allowed_no_install_app_but_from_bitrix24';
                    $logData['error'] = $testResult['error'];
                    $logData['warning'] = 'Settings.json may be corrupted or contain test data';
                    $this->logger->logAuthCheck('Auth check passed with warning', $logData);
                    // Разрешаем доступ, если запрос точно из Bitrix24
                    return true;
                }
                
                // Другие ошибки авторизации - доступ запрещён
                if (in_array($testResult['error'], ['invalid_token', 'invalid_grant', 'invalid_client', 'NO_AUTH_FOUND'])) {
                    $logData['result'] = 'denied_token_invalid';
                    $logData['error'] = $testResult['error'];
                    $this->logger->logAuthCheck('Auth check failed', $logData);
                    return false;
                }
            }
            
            // Если запрос успешен - проверяем права доступа (если включена проверка)
            $currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;
            $portalDomain = $_REQUEST['DOMAIN'] ?? null;
            
            // Получаем домен из settings.json, если не передан в запросе
            if (!$portalDomain && isset($settings['domain']) && !empty($settings['domain'])) {
                $portalDomain = $settings['domain'];
            }
            if (!$portalDomain && isset($settings['client_endpoint']) && !empty($settings['client_endpoint'])) {
                if (preg_match('#https?://([^/]+)#', $settings['client_endpoint'], $matches)) {
                    $portalDomain = $matches[1];
                }
            }
            
            // Если есть токен текущего пользователя и домен - проверяем права доступа
            if ($currentUserAuthId && $portalDomain && $portalDomain !== 'oauth.bitrix.info') {
                // Получаем данные пользователя
                $user = $this->userService->getCurrentUser($currentUserAuthId, $portalDomain);
                
                if ($user && isset($user['ID'])) {
                    $userId = $user['ID'];
                    $userDepartments = $this->userService->getUserDepartments($user);
                    
                    // Проверяем, является ли пользователь администратором
                    $isAdmin = $this->userService->isAdmin($user, $currentUserAuthId, $portalDomain);
                    
                    // Если не администратор - проверяем права доступа
                    if (!$isAdmin) {
                        $hasAccess = $this->accessControlService->checkUserAccess($userId, $userDepartments, $currentUserAuthId, $portalDomain);
                        
                        if (!$hasAccess) {
                            // Доступ запрещён — редирект на failure.php
                            $logData['result'] = 'denied_no_access_rights';
                            $logData['user_id'] = $userId;
                            $this->logger->logAuthCheck('Auth check failed - no access rights', $logData);
                            $this->redirectToFailure();
                            return false;
                        }
                    }
                }
            }
            
            // Если запрос успешен - разрешаем доступ
            $logData['result'] = 'allowed';
            $logData['token_valid'] = true;
            $logData['is_from_bitrix24'] = $isFromBitrix24;
            $logData['access_type'] = $isFromBitrix24 ? 'bitrix24_iframe' : 'direct_access_with_installer_token';
            $this->logger->logAuthCheck('Auth check passed', $logData);
            return true;
        } catch (\Exception $e) {
            // Ошибка при проверке - доступ запрещён
            $logData['result'] = 'denied_exception';
            $logData['exception'] = $e->getMessage();
            $this->logger->logAuthCheck('Auth check failed - exception', $logData);
            return false;
        }
    }
    
    /**
     * Проверка, идет ли запрос из Bitrix24 (через iframe)
     * 
     * @return bool true если запрос из Bitrix24
     */
    public function isRequestFromBitrix24(): bool
    {
        // Проверка 1: наличие параметров, которые Bitrix24 передаёт при открытии приложения
        if (
            (isset($_REQUEST['DOMAIN']) && !empty($_REQUEST['DOMAIN'])) ||
            (isset($_REQUEST['AUTH_ID']) && !empty($_REQUEST['AUTH_ID'])) ||
            (isset($_REQUEST['APP_SID']) && !empty($_REQUEST['APP_SID']))
        ) {
            return true;
        }
        
        // Проверка 2: Referer header указывает на домен Bitrix24
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            // Проверяем, что Referer содержит домен Bitrix24
            $settings = $this->configService->getSettings();
            if (isset($settings['domain']) && !empty($settings['domain'])) {
                $bitrixDomain = $settings['domain'];
                if (strpos($referer, $bitrixDomain) !== false || 
                    strpos($referer, 'bitrix24') !== false ||
                    strpos($referer, 'marketplace') !== false) {
                    return true;
                }
            }
            // Также проверяем общие паттерны Bitrix24
            if (strpos($referer, 'bitrix24') !== false || strpos($referer, 'marketplace') !== false) {
                return true;
            }
        }
        
        // Проверка 3: User-Agent может содержать информацию о Bitrix24
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if (stripos($userAgent, 'bitrix') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Редирект на страницу ошибки доступа
     */
    public function redirectToFailure(): void
    {
        // Определяем протокол (HTTP или HTTPS)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        
        // Определяем хост
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        // Определяем базовый путь
        $scriptPath = dirname($_SERVER['PHP_SELF']);
        $scriptPath = rtrim($scriptPath, '/');
        
        // Формируем абсолютный URL для failure.php
        if ($scriptPath === '' || $scriptPath === '.') {
            $failureUrl = $protocol . '://' . $host . '/failure.php';
        } else {
            $failureUrl = $protocol . '://' . $host . $scriptPath . '/failure.php';
        }
        
        // Очищаем буфер вывода перед отправкой заголовков
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Отправляем заголовки редиректа
        header('HTTP/1.1 403 Forbidden', true, 403);
        header('Location: ' . $failureUrl, true, 302);
        header('Content-Type: text/html; charset=UTF-8');
        
        // Выводим сообщение на случай, если редирект не сработает
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($failureUrl) . '"></head><body><p>Redirecting to <a href="' . htmlspecialchars($failureUrl) . '">error page</a>...</p></body></html>';
        
        exit;
    }
}

