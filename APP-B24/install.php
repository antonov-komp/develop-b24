<?php
/**
 * Страница установки приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только при установке из Bitrix24
 * Использует AuthService для проверки авторизации
 * Установка приложения Bitrix24
 * Использует b24phpsdk для работы с API
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
    
    // Обработка события ONAPPINSTALL (установка через REST API)
    if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'ONAPPINSTALL' && !empty($_REQUEST['auth'])) {
        try {
            $auth = $_REQUEST['auth'];
            
            // Валидация данных
            if (empty($auth['access_token']) || empty($auth['domain'])) {
                throw new \Exception('Missing required fields: access_token or domain');
            }
            
            // Очистка домена от протокола
            $domain = preg_replace('#^https?://#', '', $auth['domain']);
            $domain = rtrim($domain, '/');
            
            // Сохранение настроек
            $settings = [
                'access_token' => $auth['access_token'],
                'expires_in' => isset($auth['expires_in']) ? (int)$auth['expires_in'] : 3600,
                'application_token' => $auth['application_token'] ?? '',
                'refresh_token' => $auth['refresh_token'] ?? '',
                'domain' => $domain,
                'client_endpoint' => 'https://' . $domain . '/rest/',
                'installed_at' => date('Y-m-d H:i:s'),
                'installed_by' => 'ONAPPINSTALL'
            ];
            
            $settingsFile = __DIR__ . '/settings.json';
            $settingsDir = dirname($settingsFile);
            
            // Проверка прав на запись
            if (!is_writable($settingsDir)) {
                throw new \Exception('Settings directory is not writable: ' . $settingsDir);
            }
            
            // Сохранение с блокировкой файла
            $result = file_put_contents(
                $settingsFile,
                json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
            
            if ($result === false) {
                throw new \Exception('Failed to write settings.json');
            }
            
            // Установка прав доступа
            chmod($settingsFile, 0600);
            
            // Логирование успешной установки
            if (isset($logger)) {
                $logger->log('Application installed via ONAPPINSTALL', [
                    'domain' => $domain,
                    'token_length' => strlen($auth['access_token'])
                ], 'info');
            }
            
            // REST-only режим - возвращаем JSON и завершаем
            header('Content-Type: application/json');
            echo json_encode([
                'rest_only' => true,
                'install' => true,
                'domain' => $domain
            ]);
            exit;
            
        } catch (\Exception $e) {
            // Логирование ошибки
            if (isset($logger)) {
                $logger->logError('Install error (ONAPPINSTALL)', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            } else {
                error_log('Install error (ONAPPINSTALL): ' . $e->getMessage());
            }
            
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'rest_only' => true,
                'install' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Обработка события PLACEMENT (установка через placement)
    if (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] == 'DEFAULT') {
        try {
            // Валидация данных
            if (empty($_REQUEST['AUTH_ID']) || empty($_REQUEST['DOMAIN'])) {
                throw new \Exception('Missing required fields: AUTH_ID or DOMAIN');
            }
            
            // Очистка домена от протокола
            $domain = preg_replace('#^https?://#', '', $_REQUEST['DOMAIN']);
            $domain = rtrim($domain, '/');
            
            // Получаем данные администратора (пользователя, который устанавливает приложение)
            $adminUserData = null;
            try {
                $userAuthId = htmlspecialchars($_REQUEST['AUTH_ID'], ENT_QUOTES, 'UTF-8');
                $userDomain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
                
                // Используем токен пользователя для получения его данных
                if (isset($userService) && isset($apiService)) {
                    $user = $userService->getCurrentUser($userAuthId, $userDomain);
                    if ($user && isset($user['ID'])) {
                        $adminUserData = [
                            'id' => $user['ID'] ?? null,
                            'name' => $user['NAME'] ?? '',
                            'last_name' => $user['LAST_NAME'] ?? '',
                            'email' => $user['EMAIL'] ?? '',
                            'admin' => $user['ADMIN'] ?? 'N'
                        ];
                        
                        if (isset($logger)) {
                            $logger->log('Admin user data retrieved during installation', [
                                'user_id' => $adminUserData['id'],
                                'is_admin' => $adminUserData['admin']
                            ], 'info');
                        }
                    }
                }
            } catch (\Exception $e) {
                // Не критично - продолжаем установку без данных администратора
                if (isset($logger)) {
                    $logger->logError('Failed to get admin user data during installation', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Сохранение настроек
            $settings = [
                'access_token' => htmlspecialchars($_REQUEST['AUTH_ID'], ENT_QUOTES, 'UTF-8'),
                'expires_in' => isset($_REQUEST['AUTH_EXPIRES']) ? (int)$_REQUEST['AUTH_EXPIRES'] : 3600,
                'application_token' => isset($_REQUEST['APP_SID']) ? htmlspecialchars($_REQUEST['APP_SID'], ENT_QUOTES, 'UTF-8') : '',
                'refresh_token' => isset($_REQUEST['REFRESH_ID']) ? htmlspecialchars($_REQUEST['REFRESH_ID'], ENT_QUOTES, 'UTF-8') : '',
                'domain' => htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'),
                'client_endpoint' => 'https://' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '/rest/',
                'installed_at' => date('Y-m-d H:i:s'),
                'installed_by' => 'PLACEMENT'
            ];
            
            // Добавляем данные администратора, если они получены
            if ($adminUserData) {
                $settings['admin_user'] = $adminUserData;
            }
            
            $settingsFile = __DIR__ . '/settings.json';
            $settingsDir = dirname($settingsFile);
            
            // Проверка прав на запись
            if (!is_writable($settingsDir)) {
                throw new \Exception('Settings directory is not writable: ' . $settingsDir);
            }
            
            // Сохранение с блокировкой файла
            $result = file_put_contents(
                $settingsFile,
                json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
            
            if ($result === false) {
                throw new \Exception('Failed to write settings.json');
            }
            
            // Установка прав доступа
            chmod($settingsFile, 0600);
            
            // Логирование успешной установки
            if (isset($logger)) {
                $logger->log('Application installed via PLACEMENT', [
                    'domain' => $domain,
                    'token_length' => strlen($_REQUEST['AUTH_ID'])
                ], 'info');
            }
            
            // Установка выполнена, отображаем шаблон
            $result = [
                'rest_only' => false,
                'install' => true,
                'domain' => $domain
            ];
            include(__DIR__ . '/templates/install.php');
            exit;
            
        } catch (\Exception $e) {
            // Логирование ошибки
            if (isset($logger)) {
                $logger->logError('Install error (PLACEMENT)', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            } else {
                error_log('Install error (PLACEMENT): ' . $e->getMessage());
            }
            
            // Отображение шаблона с ошибкой
            $error = 'Произошла ошибка при установке: ' . $e->getMessage();
            $result = [
                'rest_only' => false,
                'install' => false
            ];
            include(__DIR__ . '/templates/install.php');
            exit;
        }
    }
    
    // Если ни одно событие не обработано - отображаем шаблон с ошибкой
    $error = 'Неверные параметры установки. Ожидается событие ONAPPINSTALL или PLACEMENT.';
    $result = [
        'rest_only' => false,
        'install' => false
    ];
    include(__DIR__ . '/templates/install.php');
    
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
