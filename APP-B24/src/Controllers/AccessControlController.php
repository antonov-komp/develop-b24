<?php

namespace App\Controllers;

use App\Services\LoggerService;
use App\Services\ConfigService;
use App\Services\Bitrix24ApiService;
use App\Services\UserService;
use App\Services\AccessControlService;
use App\Services\AuthService;
use App\Helpers\DomainResolver;

require_once(__DIR__ . '/../../crest.php');

/**
 * Контроллер страницы управления правами доступа
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class AccessControlController extends BaseController
{
    protected LoggerService $logger;
    protected ConfigService $configService;
    protected Bitrix24ApiService $apiService;
    protected UserService $userService;
    protected AccessControlService $accessControlService;
    protected AuthService $authService;
    protected DomainResolver $domainResolver;
    
    public function __construct(
        LoggerService $logger,
        ConfigService $configService,
        Bitrix24ApiService $apiService,
        UserService $userService,
        AccessControlService $accessControlService,
        AuthService $authService,
        DomainResolver $domainResolver
    ) {
        parent::__construct();
        
        $this->logger = $logger;
        $this->configService = $configService;
        $this->apiService = $apiService;
        $this->userService = $userService;
        $this->accessControlService = $accessControlService;
        $this->authService = $authService;
        $this->domainResolver = $domainResolver;
    }
    
    /**
     * Отображение страницы управления правами доступа
     */
    public function index(): void
    {
        // Проверка авторизации
        if (!$this->authService->checkBitrix24Auth()) {
            $this->authService->redirectToFailure();
            return;
        }
        
        // Получение данных текущего пользователя
        $currentUserAuthId = $this->getRequestParam('AUTH_ID');
        $portalDomain = $this->domainResolver->resolveDomain();
        
        // Получение данных пользователя для проверки администратора
        $user = null;
        $isAdmin = false;
        
        if ($currentUserAuthId && $portalDomain) {
            $user = $this->userService->getCurrentUser($currentUserAuthId, $portalDomain);
            
            if ($user) {
                $isAdmin = $this->userService->isAdmin($user, $currentUserAuthId, $portalDomain);
            }
        }
        
        // Если пользователь не администратор - показываем ошибку доступа
        if (!$isAdmin) {
            $this->render('access-denied', [
                'title' => 'Доступ запрещён - Bitrix24 Приложение',
                'currentUserAuthId' => $currentUserAuthId,
                'portalDomain' => $portalDomain
            ]);
            return;
        }
        
        // Обработка POST-запросов
        $message = null;
        $messageType = null; // 'success' или 'error'
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->handlePostRequest($user, $currentUserAuthId, $portalDomain);
            
            if ($result['redirect']) {
                // Редирект после успешного сохранения
                $this->redirect($result['url']);
                return;
            }
            
            $message = $result['message'];
            $messageType = $result['messageType'];
        }
        
        // Проверка успешного сохранения через GET-параметр
        if (isset($_GET['success']) && isset($_GET['action'])) {
            $action = $_GET['action'] ?? '';
            $message = $this->getSuccessMessage($action);
            $messageType = 'success';
        }
        
        // Получение текущей конфигурации
        $accessConfig = $this->configService->getAccessConfig();
        
        // Получение списка всех отделов для выпадающего списка
        $allDepartments = [];
        if ($currentUserAuthId && $portalDomain) {
            $allDepartments = $this->apiService->getAllDepartments($currentUserAuthId, $portalDomain);
        }
        
        // Получение списка всех пользователей для выпадающего списка
        $allUsers = [];
        if ($currentUserAuthId && $portalDomain) {
            $allUsers = $this->apiService->getAllUsers($currentUserAuthId, $portalDomain);
        }
        
        // Формирование данных для шаблона
        $data = [
            'title' => 'Управление правами доступа - Bitrix24 Приложение',
            'message' => $message,
            'messageType' => $messageType,
            'accessConfig' => $accessConfig,
            'allDepartments' => $allDepartments,
            'allUsers' => $allUsers,
            'currentUserAuthId' => $currentUserAuthId,
            'portalDomain' => $portalDomain
        ];
        
        // Рендеринг шаблона
        $this->render('access-control', $data);
    }
    
    /**
     * Обработка POST-запросов
     * 
     * @param array|null $user Данные пользователя
     * @param string|null $currentUserAuthId Токен авторизации
     * @param string|null $portalDomain Домен портала
     * @return array Результат обработки
     */
    protected function handlePostRequest(?array $user, ?string $currentUserAuthId, ?string $portalDomain): array
    {
        $action = $_POST['action'] ?? null;
        $performedBy = [
            'id' => $user['ID'] ?? 0,
            'name' => trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? ''))
        ];
        
        if (empty($performedBy['name'])) {
            $performedBy['name'] = 'Пользователь #' . ($user['ID'] ?? 'неизвестен');
        }
        
        $message = null;
        $messageType = null;
        $redirect = false;
        $redirectUrl = null;
        
        switch ($action) {
            case 'toggle_enabled':
                $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';
                $result = $this->accessControlService->toggleAccessControl($enabled, $performedBy);
                
                if ($result['success']) {
                    $this->logger->logAccessControl('toggle_enabled', ['enabled' => $enabled, 'performed_by' => $performedBy, 'success' => true]);
                    $message = $enabled ? 'Проверка прав доступа включена' : 'Проверка прав доступа выключена';
                    $messageType = 'success';
                    $redirect = true;
                } else {
                    $this->logger->logAccessControl('toggle_enabled', ['enabled' => $enabled, 'error' => $result['error'] ?? 'unknown', 'performed_by' => $performedBy, 'success' => false]);
                    $message = $result['error'] ?? 'Ошибка при изменении настройки';
                    $messageType = 'error';
                }
                break;
                
            case 'add_department':
                $departmentId = (int)($_POST['department_id'] ?? 0);
                $departmentName = trim($_POST['department_name'] ?? '');
                
                if ($departmentId > 0 && !empty($departmentName)) {
                    $result = $this->accessControlService->addDepartment($departmentId, $departmentName, $performedBy);
                    
                    if ($result['success']) {
                        $this->logger->logAccessControl('add_department', ['id' => $departmentId, 'name' => $departmentName, 'performed_by' => $performedBy, 'success' => true]);
                        $message = 'Отдел добавлен в список доступа';
                        $messageType = 'success';
                        $redirect = true;
                    } else {
                        $this->logger->logAccessControl('add_department', ['id' => $departmentId, 'name' => $departmentName, 'error' => $result['error'] ?? 'unknown', 'performed_by' => $performedBy, 'success' => false]);
                        $message = $result['error'] ?? 'Ошибка при добавлении отдела';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Не указан отдел или название отдела';
                    if ($departmentId <= 0) {
                        $message .= ' (ID отдела не указан)';
                    }
                    if (empty($departmentName)) {
                        $message .= ' (Название отдела не указано)';
                    }
                    $messageType = 'error';
                }
                break;
                
            case 'remove_department':
                $departmentId = (int)($_POST['department_id'] ?? 0);
                
                if ($departmentId > 0) {
                    if ($this->accessControlService->removeDepartment($departmentId)) {
                        $this->logger->logAccessControl('remove_department', ['id' => $departmentId, 'performed_by' => $performedBy, 'success' => true]);
                        $message = 'Отдел удалён из списка доступа';
                        $messageType = 'success';
                        $redirect = true;
                    } else {
                        $this->logger->logAccessControl('remove_department', ['id' => $departmentId, 'performed_by' => $performedBy, 'success' => false]);
                        $message = 'Ошибка при удалении отдела';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'add_user':
                try {
                    $userId = (int)($_POST['user_id'] ?? 0);
                    $userName = trim($_POST['user_name'] ?? '');
                    $userEmail = !empty($_POST['user_email']) ? trim($_POST['user_email']) : null;
                    
                    if ($userId > 0 && !empty($userName)) {
                        $result = $this->accessControlService->addUser($userId, $userName, $userEmail, $performedBy);
                        
                        if ($result['success']) {
                            $this->logger->logAccessControl('add_user', ['id' => $userId, 'name' => $userName, 'email' => $userEmail, 'performed_by' => $performedBy, 'success' => true]);
                            $message = 'Пользователь добавлен в список доступа';
                            $messageType = 'success';
                            $redirect = true;
                        } else {
                            $this->logger->logAccessControl('add_user', ['id' => $userId, 'name' => $userName, 'email' => $userEmail, 'error' => $result['error'] ?? 'unknown', 'performed_by' => $performedBy, 'success' => false]);
                            $message = $result['error'] ?? 'Ошибка при добавлении пользователя';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Не указан пользователь или имя пользователя';
                        if ($userId <= 0) {
                            $message .= ' (ID пользователя не указан)';
                        }
                        if (empty($userName)) {
                            $message .= ' (Имя пользователя не указано)';
                        }
                        $messageType = 'error';
                    }
                } catch (\Exception $e) {
                    $this->logger->logError('Error adding user to access control', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    $message = 'Ошибка при добавлении пользователя: ' . $e->getMessage();
                    $messageType = 'error';
                } catch (\Error $e) {
                    $this->logger->logError('Critical error adding user to access control', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    $message = 'Критическая ошибка при добавлении пользователя: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'remove_user':
                $userId = (int)($_POST['user_id'] ?? 0);
                
                if ($userId > 0) {
                    if ($this->accessControlService->removeUser($userId)) {
                        $this->logger->logAccessControl('remove_user', ['id' => $userId, 'performed_by' => $performedBy, 'success' => true]);
                        $message = 'Пользователь удалён из списка доступа';
                        $messageType = 'success';
                        $redirect = true;
                    } else {
                        $this->logger->logAccessControl('remove_user', ['id' => $userId, 'performed_by' => $performedBy, 'success' => false]);
                        $message = 'Ошибка при удалении пользователя';
                        $messageType = 'error';
                    }
                }
                break;
        }
        
        // Формирование URL редиректа
        if ($redirect && $messageType === 'success') {
            $redirectUrl = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '/APP-B24/access-control.php';
            
            $params = [];
            
            if (!empty($currentUserAuthId)) {
                $params['AUTH_ID'] = $currentUserAuthId;
            }
            if (!empty($portalDomain)) {
                $params['DOMAIN'] = $portalDomain;
            }
            
            $params['success'] = '1';
            $params['action'] = $action;
            
            $redirectUrl .= '?' . http_build_query($params);
        }
        
        return [
            'message' => $message,
            'messageType' => $messageType,
            'redirect' => $redirect,
            'url' => $redirectUrl
        ];
    }
    
    /**
     * Получение сообщения об успехе по действию
     * 
     * @param string $action Действие
     * @return string Сообщение
     */
    protected function getSuccessMessage(string $action): string
    {
        switch ($action) {
            case 'add_user':
                return 'Пользователь добавлен в список доступа';
            case 'add_department':
                return 'Отдел добавлен в список доступа';
            case 'remove_user':
                return 'Пользователь удалён из списка доступа';
            case 'remove_department':
                return 'Отдел удалён из списка доступа';
            case 'toggle_enabled':
                return 'Настройки сохранены';
            default:
                return 'Операция выполнена успешно';
        }
    }
}

