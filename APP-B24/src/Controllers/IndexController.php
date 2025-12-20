<?php

namespace App\Controllers;

use App\Services\LoggerService;
use App\Services\ConfigService;
use App\Services\Bitrix24ApiService;
use App\Services\UserService;
use App\Services\AccessControlService;
use App\Services\AuthService;
use App\Helpers\DomainResolver;
use App\Helpers\AdminChecker;

require_once(__DIR__ . '/../../crest.php');

/**
 * Контроллер главной страницы
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class IndexController extends BaseController
{
    protected LoggerService $logger;
    protected ConfigService $configService;
    protected Bitrix24ApiService $apiService;
    protected UserService $userService;
    protected AccessControlService $accessControlService;
    protected AuthService $authService;
    protected DomainResolver $domainResolver;
    protected AdminChecker $adminChecker;
    
    public function __construct(
        LoggerService $logger,
        ConfigService $configService,
        Bitrix24ApiService $apiService,
        UserService $userService,
        AccessControlService $accessControlService,
        AuthService $authService,
        DomainResolver $domainResolver,
        AdminChecker $adminChecker
    ) {
        parent::__construct();
        
        $this->logger = $logger;
        $this->configService = $configService;
        $this->apiService = $apiService;
        $this->userService = $userService;
        $this->accessControlService = $accessControlService;
        $this->authService = $authService;
        $this->domainResolver = $domainResolver;
        $this->adminChecker = $adminChecker;
    }
    
    /**
     * Показ страницы ошибки конфига
     * 
     * @param string $message Сообщение для пользователя
     * @param string|null $lastUpdated Дата последнего обновления конфига
     */
    protected function showConfigErrorPage(string $message, ?string $lastUpdated = null): void
    {
        // Формируем URL для страницы ошибки
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['PHP_SELF']);
        $scriptPath = rtrim($scriptPath, '/');
        
        if ($scriptPath === '' || $scriptPath === '.') {
            $errorUrl = $protocol . '://' . $host . '/config-error.php';
        } else {
            $errorUrl = $protocol . '://' . $host . $scriptPath . '/config-error.php';
        }
        
        // Добавляем параметры
        $params = [];
        if ($message) {
            $params['message'] = $message;
        }
        if ($lastUpdated) {
            $params['last_updated'] = $lastUpdated;
        }
        
        if (!empty($params)) {
            $errorUrl .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }
        
        $this->redirect($errorUrl);
    }
    
    /**
     * Отображение главной страницы
     */
    public function index(): void
    {
        // Проверка авторизации Bitrix24
        if (!$this->authService->checkBitrix24Auth()) {
            $this->authService->redirectToFailure();
            return;
        }
        
        // Проверка, идет ли запрос из Bitrix24 (через iframe)
        $isFromBitrix24 = $this->authService->isRequestFromBitrix24();
        
        // Проверка конфигурации доступа к главной странице
        // ВАЖНО: Если запрос идет из Bitrix24 (iframe) - всегда разрешаем доступ, проверка конфига не нужна
        // Если запрос прямой (прямой URL) - проверяем конфиг, и если enabled: true, то разрешаем
        if (!$isFromBitrix24) {
            // Прямой доступ - проверяем конфиг
            $indexConfig = $this->configService->getIndexPageConfig();
            if (!$indexConfig['enabled']) {
                $this->logger->logConfigCheck('CONFIG CHECK FAILED: enabled=false, redirecting to config-error.php (direct access)');
                $this->showConfigErrorPage(
                    $indexConfig['message'] ?? 'Интерфейс приложения временно недоступен.',
                    $indexConfig['last_updated'] ?? null
                );
                return;
            }
            $this->logger->logConfigCheck('CONFIG CHECK PASSED: enabled=true (direct access allowed)');
        } else {
            // Запрос из Bitrix24 - всегда разрешаем, проверка конфига не нужна
            $this->logger->logConfigCheck('CONFIG CHECK SKIPPED: Request from Bitrix24 iframe, always allowed');
        }
        
        // Проверка прав доступа (если включена)
        $accessConfig = $this->configService->getAccessConfig();
        if ($accessConfig['access_control']['enabled']) {
            // Получаем данные текущего пользователя
            $currentUserAuthId = $this->getRequestParam('AUTH_ID');
            $portalDomain = $this->domainResolver->resolveDomain();
            
            if ($currentUserAuthId && $portalDomain && $portalDomain !== 'oauth.bitrix.info') {
                // Получаем данные пользователя
                $user = $this->userService->getCurrentUser($currentUserAuthId, $portalDomain);
                
                if ($user && isset($user['ID'])) {
                    $userId = $user['ID'];
                    $userDepartments = $this->userService->getUserDepartments($user);
                    
                    // Проверяем, является ли пользователь администратором
                    $isAdmin = $this->userService->isAdmin($user, $currentUserAuthId, $portalDomain);
                    
                    // Если не администратор — проверяем права доступа
                    if (!$isAdmin) {
                        $hasAccess = $this->accessControlService->checkUserAccess($userId, $userDepartments, $currentUserAuthId, $portalDomain);
                        
                        if (!$hasAccess) {
                            // Доступ запрещён — редирект на failure.php
                            $this->logger->logConfigCheck('ACCESS DENIED: User does not have access rights');
                            $this->authService->redirectToFailure();
                            return;
                        }
                    }
                }
            }
        }
        
        $this->logger->logConfigCheck('ACCESS GRANTED: Auth and config checks passed, showing interface');
        
        // Подключаем CREST для работы с Bitrix24 API
        $this->logger->logConfigCheck('CREST loaded successfully');
        
        // Получение токена текущего пользователя из параметров запроса
        $currentUserAuthId = $this->getRequestParam('AUTH_ID');
        
        // Логирование для отладки
        $debugLog = [
            'has_auth_id' => !empty($currentUserAuthId),
            'auth_id_length' => $currentUserAuthId ? strlen($currentUserAuthId) : 0,
            'request_params' => array_keys($_REQUEST),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $this->logger->log('User check', $debugLog, 'info');
        
        // Получение домена портала через DomainResolver
        $portalDomain = $this->domainResolver->resolveDomain();
        
        // Получение данных текущего пользователя
        $user = null;
        $isCurrentUserToken = false; // Флаг: используется ли токен текущего пользователя
        
        if ($currentUserAuthId && $portalDomain) {
            // Используем токен текущего пользователя для получения его данных
            $isCurrentUserToken = true;
            $user = $this->userService->getCurrentUser($currentUserAuthId, $portalDomain);
            
            if (!$user) {
                die('<h1>Ошибка получения данных пользователя</h1><p>Не удалось получить данные пользователя через API</p>');
            }
            
            // Если поле ADMIN отсутствует, делаем дополнительный запрос через user.get
            // Метод: user.get
            // Документация: https://context7.com/bitrix24/rest/user.get
            if (!isset($user['ADMIN']) && isset($user['ID'])) {
                $userId = $user['ID'];
                $fullUser = $this->userService->getUserById($userId, $currentUserAuthId, $portalDomain);
                
                if ($fullUser) {
                    // Объединяем данные, приоритет у данных из user.get (там есть ADMIN)
                    $user = array_merge($user, $fullUser);
                }
            }
        } else {
            // Fallback: если нет токена текущего пользователя, используем токен установщика
            // (но это будет владелец токена, а не текущий пользователь)
            $isCurrentUserToken = false;
            $userResult = $this->apiService->call('user.current', []);
            
            if (isset($userResult['error'])) {
                $errorMessage = $userResult['error_description'] ?? $userResult['error'];
                die('<h1>Ошибка получения данных пользователя</h1><p>' . htmlspecialchars($errorMessage) . '</p>');
            }
            
            $user = $userResult['result'] ?? null;
            
            // Для токена установщика тоже пытаемся получить ADMIN через user.get
            if ($user && !isset($user['ADMIN']) && isset($user['ID'])) {
                $userId = $user['ID'];
                $getUserResult = $this->apiService->call('user.get', [
                    'ID' => $userId,
                    'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL', 'ADMIN', 'PERSONAL_PHOTO', 'TIME_ZONE', 'UF_DEPARTMENT']
                ]);
                
                if (isset($getUserResult['result'][0]) && is_array($getUserResult['result'][0])) {
                    $user = array_merge($user, $getUserResult['result'][0]);
                }
            }
        }
        
        if (!$user || !isset($user['ID'])) {
            die('<h1>Ошибка: данные пользователя не получены</h1>');
        }
        
        // Домен портала уже получен выше, используем его или устанавливаем значение по умолчанию
        if (!$portalDomain) {
            $portalDomain = $this->domainResolver->resolveDomain();
            
            // Если все еще не определен, используем значение по умолчанию
            if (!$portalDomain) {
                $portalDomain = 'не указан';
                $this->logger->logConfigCheck('WARNING: Portal domain not found, using default');
            }
        }
        
        // Формирование данных пользователя
        $userFullName = $this->userService->getUserFullName($user);
        
        // Проверка статуса администратора через UserService
        $isAdmin = $this->userService->isAdmin($user, $currentUserAuthId ?? '', $portalDomain ?? '');
        
        // Логирование для отладки
        $adminDebugLog = [
            'user_id' => $user['ID'] ?? 'unknown',
            'user_name' => ($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? ''),
            'admin_field_exists' => isset($user['ADMIN']),
            'admin_value' => $user['ADMIN'] ?? 'not_set',
            'admin_value_type' => isset($user['ADMIN']) ? gettype($user['ADMIN']) : 'not_set',
            'is_admin_field_exists' => isset($user['IS_ADMIN']),
            'is_admin_value' => $user['IS_ADMIN'] ?? 'not_set',
            'is_admin_result' => $isAdmin,
            'check_method' => isset($user['ADMIN']) ? 'ADMIN_field' : (isset($user['IS_ADMIN']) ? 'IS_ADMIN_field' : 'user.admin_method'),
            'all_user_fields' => array_keys($user),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $this->logger->log('Admin check', $adminDebugLog, 'info');
        
        $adminStatus = $isAdmin ? 'Администратор на портале' : 'Пользователь';
        
        // Фото пользователя (если есть)
        $userPhoto = $user['PERSONAL_PHOTO'] ?? null;
        
        // Получение данных об отделе пользователя
        $departmentId = null;
        $departmentName = null;
        
        // Получаем ID отдела из поля UF_DEPARTMENT (массив ID отделов)
        $userDepartments = $this->userService->getUserDepartments($user);
        
        if (!empty($userDepartments)) {
            // Логирование для отладки
            $deptDebugLog = [
                'user_id' => $user['ID'] ?? 'unknown',
                'uf_department_exists' => isset($user['UF_DEPARTMENT']),
                'uf_department_type' => isset($user['UF_DEPARTMENT']) ? gettype($user['UF_DEPARTMENT']) : 'not_set',
                'uf_department_value' => $user['UF_DEPARTMENT'] ?? 'not_set',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $this->logger->log('Department check', $deptDebugLog, 'info');
            
            // Берем первый отдел (основной отдел пользователя)
            $departmentId = $userDepartments[0];
            
            // Получаем данные отдела через API
            // ВАЖНО: Токен может не иметь прав на department.get
            // Пробуем получить название, но если ошибка - просто показываем ID
            if ($departmentId > 0) {
                // Пробуем получить название отдела через токен установщика (CRest)
                // Метод: department.get
                // Документация: https://context7.com/bitrix24/rest/department.get
                try {
                    $departmentData = $this->apiService->getDepartment($departmentId, $currentUserAuthId ?? '', $portalDomain ?? '');
                    
                    if ($departmentData) {
                        $departmentName = $departmentData['NAME'] ?? null;
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки - просто не получим название отдела
                    // Будет показан только ID
                }
            }
        }
        
        // Формирование данных для шаблона
        $data = [
            'title' => 'Приветствие - Bitrix24 Приложение',
            'user' => $user,
            'userFullName' => $userFullName,
            'isAdmin' => $isAdmin,
            'adminStatus' => $adminStatus,
            'portalDomain' => $portalDomain,
            'departmentId' => $departmentId,
            'departmentName' => $departmentName,
            'userPhoto' => $userPhoto,
            'isCurrentUserToken' => $isCurrentUserToken,
            'debugMode' => isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1'
        ];
        
        // Рендеринг шаблона
        $this->render('index', $data);
    }
}

