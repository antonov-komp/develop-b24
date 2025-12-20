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
 * Контроллер страницы анализа токена
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class TokenAnalysisController extends BaseController
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
     * Проверка доступа к методу API
     * 
     * @param string $method Название метода API
     * @param string $authId Токен авторизации (не используется, так как используется токен установщика)
     * @param string $domain Домен портала (не используется, так как используется токен установщика)
     * @return array Результат проверки
     */
    protected function checkApiMethodAccess(string $method, string $authId, string $domain): array
    {
        $startTime = microtime(true);
        
        try {
            // Используем Bitrix24ApiService для вызова метода (токен установщика)
            $result = $this->apiService->call($method, ['limit' => 1]);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if (isset($result['error'])) {
                return [
                    'accessible' => false,
                    'error' => $result['error'],
                    'error_description' => $result['error_description'] ?? 'Unknown error',
                    'execution_time_ms' => $executionTime
                ];
            }
            
            // Если нет ошибки, значит метод доступен
            return [
                'accessible' => true,
                'error' => null,
                'error_description' => null,
                'execution_time_ms' => $executionTime
            ];
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'accessible' => false,
                'error' => 'exception',
                'error_description' => $e->getMessage(),
                'execution_time_ms' => $executionTime
            ];
        }
    }
    
    /**
     * Комплексный анализ токена Bitrix24
     * 
     * @param string|null $authId Токен авторизации
     * @param string|null $domain Домен портала
     * @return array Результат анализа
     */
    protected function analyzeToken(?string $authId, ?string $domain): array
    {
        $analysisStartTime = microtime(true);
        
        $analysis = [
            'analysis_timestamp' => date('Y-m-d H:i:s'),
            'token_info' => [],
            'token_owner' => [],
            'permissions' => [
                'is_admin' => false,
                'admin_check_method' => null,
                'api_methods' => []
            ],
            'portal_info' => [
                'domain' => $domain ?? 'не указан',
                'domain_source' => 'resolved'
            ],
            'errors' => []
        ];
        
        // Анализ токена
        if (empty($authId)) {
            // Если токен не передан в параметрах, пробуем использовать токен установщика через CRest
            $settingsFile = __DIR__ . '/../../settings.json';
            if (file_exists($settingsFile)) {
                $settingsContent = file_get_contents($settingsFile);
                $settings = json_decode($settingsContent, true);
                
                // Пробуем получить данные через токен установщика
                $userResult = $this->apiService->call('user.current', []);
                
                if (isset($userResult['error'])) {
                    $analysis['errors'][] = 'Токен не найден в параметрах запроса и токен установщика недействителен: ' . ($userResult['error_description'] ?? $userResult['error']);
                    $analysis['token_info'] = [
                        'exists' => false,
                        'length' => 0,
                        'preview' => null,
                        'type' => null
                    ];
                    return $analysis;
                }
                
                // Если токен установщика работает, используем его
                $user = $userResult['result'] ?? null;
                if ($user) {
                    $analysis['token_info'] = [
                        'exists' => true,
                        'length' => 'unknown',
                        'preview' => 'installer_token',
                        'type' => 'installer'
                    ];
                    
                    // Получаем данные пользователя через токен установщика
                    $departmentId = null;
                    $departmentName = null;
                    
                    if (isset($user['UF_DEPARTMENT']) && is_array($user['UF_DEPARTMENT']) && !empty($user['UF_DEPARTMENT'])) {
                        $departmentId = (int)$user['UF_DEPARTMENT'][0];
                        
                        if ($departmentId > 0) {
                            $departmentResult = $this->apiService->call('department.get', ['ID' => $departmentId]);
                            if (isset($departmentResult['result']) && !isset($departmentResult['error'])) {
                                if (is_array($departmentResult['result'])) {
                                    if (isset($departmentResult['result'][0]) && is_array($departmentResult['result'][0])) {
                                        $departmentName = $departmentResult['result'][0]['NAME'] ?? null;
                                    } elseif (isset($departmentResult['result']['NAME'])) {
                                        $departmentName = $departmentResult['result']['NAME'];
                                    }
                                }
                            }
                        }
                    }
                    
                    $analysis['token_owner'] = [
                        'id' => $user['ID'] ?? null,
                        'name' => $user['NAME'] ?? null,
                        'last_name' => $user['LAST_NAME'] ?? null,
                        'full_name' => $this->userService->getUserFullName($user),
                        'email' => $user['EMAIL'] ?? null,
                        'photo' => $user['PERSONAL_PHOTO'] ?? null,
                        'time_zone' => $user['TIME_ZONE'] ?? null,
                        'department' => [
                            'id' => $departmentId,
                            'name' => $departmentName
                        ],
                        'account_created' => isset($user['DATE_REGISTER']) ? $user['DATE_REGISTER'] : null
                    ];
                    
                    // Проверка статуса администратора
                    $isAdmin = $this->userService->isAdmin($user, '', $domain ?? '');
                    $analysis['permissions']['is_admin'] = $isAdmin;
                    $analysis['permissions']['admin_check_method'] = isset($user['ADMIN']) ? 'ADMIN_field' : 'user.admin_method';
                    
                    // Проверка прав доступа к методам API через токен установщика
                    $methodsToCheck = [
                        'crm.lead.list',
                        'crm.deal.list',
                        'crm.contact.list',
                        'department.get',
                        'user.get'
                    ];
                    
                    foreach ($methodsToCheck as $method) {
                        $checkResult = $this->checkApiMethodAccess($method, '', $domain ?? '');
                        $analysis['permissions']['api_methods'][$method] = $checkResult;
                    }
                    
                    $analysisExecutionTime = round((microtime(true) - $analysisStartTime) * 1000, 2);
                    $analysis['analysis_execution_time_ms'] = $analysisExecutionTime;
                    
                    return $analysis;
                }
            }
            
            // Если ничего не получилось
            $analysis['errors'][] = 'Токен не найден в параметрах запроса';
            $analysis['token_info'] = [
                'exists' => false,
                'length' => 0,
                'preview' => null,
                'type' => null
            ];
            return $analysis;
        }
        
        // Определяем тип токена (текущего пользователя или установщика)
        $tokenType = 'current_user';
        $tokenPreview = substr($authId, 0, 4) . '...' . substr($authId, -4);
        
        $analysis['token_info'] = [
            'exists' => true,
            'length' => strlen($authId),
            'preview' => $tokenPreview,
            'type' => $tokenType
        ];
        
        // Получение данных владельца токена через сервисы
        $user = $this->userService->getCurrentUser($authId, $domain ?? '');
        
        if (!$user) {
            $analysis['errors'][] = 'Ошибка получения данных пользователя';
        } else {
            // Получаем ID отдела для получения его названия
            $departmentId = null;
            $departmentName = null;
            
            $userDepartments = $this->userService->getUserDepartments($user);
            if (!empty($userDepartments)) {
                $departmentId = $userDepartments[0];
                
                if ($departmentId > 0) {
                    $departmentData = $this->apiService->getDepartment($departmentId, $authId, $domain ?? '');
                    if ($departmentData) {
                        $departmentName = $departmentData['NAME'] ?? null;
                    }
                }
            }
            
            $analysis['token_owner'] = [
                'id' => $user['ID'] ?? null,
                'name' => $user['NAME'] ?? null,
                'last_name' => $user['LAST_NAME'] ?? null,
                'full_name' => $this->userService->getUserFullName($user),
                'email' => $user['EMAIL'] ?? null,
                'photo' => $user['PERSONAL_PHOTO'] ?? null,
                'time_zone' => $user['TIME_ZONE'] ?? null,
                'department' => [
                    'id' => $departmentId,
                    'name' => $departmentName
                ],
                'account_created' => isset($user['DATE_REGISTER']) ? $user['DATE_REGISTER'] : null
            ];
            
            // Проверка статуса администратора через сервисы
            $isAdmin = $this->userService->isAdmin($user, $authId, $domain ?? '');
            $analysis['permissions']['is_admin'] = $isAdmin;
            $analysis['permissions']['admin_check_method'] = isset($user['ADMIN']) ? 'ADMIN_field' : 'user.admin_method';
        }
        
        // Проверка прав доступа к методам API
        $methodsToCheck = [
            'crm.lead.list',
            'crm.deal.list',
            'crm.contact.list',
            'department.get',
            'user.get'
        ];
        
        foreach ($methodsToCheck as $method) {
            $checkResult = $this->checkApiMethodAccess($method, $authId ?? '', $domain ?? '');
            $analysis['permissions']['api_methods'][$method] = $checkResult;
        }
        
        // Общее время выполнения анализа
        $analysisExecutionTime = round((microtime(true) - $analysisStartTime) * 1000, 2);
        $analysis['analysis_execution_time_ms'] = $analysisExecutionTime;
        
        return $analysis;
    }
    
    /**
     * Отображение страницы анализа токена
     */
    public function index(): void
    {
        // Проверка авторизации
        if (!$this->authService->checkBitrix24Auth()) {
            $this->authService->redirectToFailure();
            return;
        }
        
        // Получение токена из параметров запроса
        $currentUserAuthId = $this->getRequestParam('AUTH_ID');
        
        // Получение домена портала через DomainResolver
        $portalDomain = $this->domainResolver->resolveDomain();
        $domainSource = 'resolved';
        
        // Если домен не найден, используем значение по умолчанию
        if (!$portalDomain) {
            $portalDomain = 'не указан';
        }
        
        // ПРОВЕРКА ПРАВ ДОСТУПА: Страница доступна только администраторам
        $isAdmin = false;
        $adminCheckError = null;
        $user = null;
        
        if ($currentUserAuthId && $portalDomain && $portalDomain !== 'не указан') {
            // Получаем данные пользователя для проверки статуса администратора
            $user = $this->userService->getCurrentUser($currentUserAuthId, $portalDomain);
            
            if ($user) {
                $isAdmin = $this->userService->isAdmin($user, $currentUserAuthId, $portalDomain);
            } else {
                $adminCheckError = 'Не удалось получить данные пользователя';
            }
        } else {
            // Если нет токена текущего пользователя, пробуем через токен установщика
            $adminCheckResult = $this->apiService->call('user.admin', []);
            if (isset($adminCheckResult['result'])) {
                $isAdmin = ($adminCheckResult['result'] === true || $adminCheckResult['result'] === 'true' || $adminCheckResult['result'] == 1);
            } else {
                $adminCheckError = 'Токен не найден и не удалось проверить статус через токен установщика';
            }
        }
        
        // Если пользователь не администратор - показываем ошибку доступа
        if (!$isAdmin) {
            // Логирование попытки доступа не-администратора
            $accessDeniedLog = [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => isset($user) ? ($user['ID'] ?? 'unknown') : 'unknown',
                'user_name' => isset($user) ? $this->userService->getUserFullName($user) : 'unknown',
                'has_token' => !empty($currentUserAuthId),
                'portal_domain' => $portalDomain,
                'admin_check_error' => $adminCheckError,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            $this->logger->log('Token analysis access denied', $accessDeniedLog, 'warning');
            
            // Показываем страницу с ошибкой доступа
            $this->render('token-analysis-denied', [
                'title' => 'Доступ запрещён - Bitrix24 Приложение',
                'currentUserAuthId' => $currentUserAuthId,
                'portalDomain' => $portalDomain
            ]);
            return;
        }
        
        // Выполнение анализа токена
        $analysisResult = $this->analyzeToken($currentUserAuthId, $portalDomain);
        
        // Обновляем источник домена в результате
        $analysisResult['portal_info']['domain_source'] = $domainSource;
        
        // Логирование анализа
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'has_token' => !empty($currentUserAuthId),
            'token_preview' => $currentUserAuthId ? (substr($currentUserAuthId, 0, 4) . '...' . substr($currentUserAuthId, -4)) : null,
            'portal_domain' => $portalDomain,
            'domain_source' => $domainSource,
            'user_id' => $analysisResult['token_owner']['id'] ?? null,
            'is_admin' => $analysisResult['permissions']['is_admin'] ?? false,
            'admin_check_method' => $analysisResult['permissions']['admin_check_method'] ?? null,
            'api_methods_checked' => count($analysisResult['permissions']['api_methods'] ?? []),
            'accessible_methods' => array_sum(array_map(function($method) {
                return $method['accessible'] ? 1 : 0;
            }, $analysisResult['permissions']['api_methods'] ?? [])),
            'errors_count' => count($analysisResult['errors'] ?? []),
            'execution_time_ms' => $analysisResult['analysis_execution_time_ms'] ?? null
        ];
        
        $this->logger->log('Token analysis completed', $logData, 'info');
        
        // Формирование данных для шаблона
        $data = [
            'title' => 'Анализ токена - Bitrix24 Приложение',
            'analysisResult' => $analysisResult,
            'currentUserAuthId' => $currentUserAuthId,
            'portalDomain' => $portalDomain
        ];
        
        // Рендеринг шаблона
        $this->render('token-analysis', $data);
    }
}

