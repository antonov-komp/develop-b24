<?php
/**
 * Страница анализа токена Bitrix24
 * 
 * Анализирует токен авторизации, его владельца и права доступа
 * Документация: https://context7.com/bitrix24/rest/
 */

require_once(__DIR__ . '/auth-check.php');

// Подключение и инициализация сервисов
require_once(__DIR__ . '/src/bootstrap.php');

require_once(__DIR__ . '/crest.php');

/**
 * Проверка доступа к методу API
 * 
 * Использует Bitrix24ApiService для проверки доступа
 * 
 * @param string $method Название метода API (например, 'crm.lead.list')
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return array Результат проверки
 */
function checkApiMethodAccess($method, $authId, $domain) {
	global $apiService;
	
	$startTime = microtime(true);
	
	try {
		// Используем Bitrix24ApiService для вызова метода
		$result = $apiService->call($method, ['limit' => 1]);
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
 * Использует сервисы для получения данных и проверки прав
 * 
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return array Результат анализа в формате массива
 */
function analyzeToken($authId, $domain) {
	global $userService, $apiService;
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
			'domain' => $domain,
			'domain_source' => 'request_params' // Будет обновлено позже
		],
		'errors' => []
	];
	
	// Анализ токена
	if (empty($authId)) {
		// Если токен не передан в параметрах, пробуем использовать токен установщика через CRest
		// Но для этого нужен домен из settings.json
		$settingsFile = __DIR__ . '/settings.json';
		if (file_exists($settingsFile)) {
			$settingsContent = file_get_contents($settingsFile);
			$settings = json_decode($settingsContent, true);
			
			// Пробуем получить данные через токен установщика
			$userResult = CRest::call('user.current', []);
			
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
						$departmentResult = CRest::call('department.get', ['ID' => $departmentId]);
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
					'full_name' => $userService->getUserFullName($user),
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
				// Для токена установщика используем пустую строку как authId
				$isAdmin = $userService->isAdmin($user, '', $domain);
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
					$startTime = microtime(true);
					$checkResult = CRest::call($method, ['limit' => 1]);
					$executionTime = round((microtime(true) - $startTime) * 1000, 2);
					
					if (isset($checkResult['error'])) {
						$analysis['permissions']['api_methods'][$method] = [
							'accessible' => false,
							'error' => $checkResult['error'],
							'error_description' => $checkResult['error_description'] ?? 'Unknown error',
							'execution_time_ms' => $executionTime
						];
					} else {
						$analysis['permissions']['api_methods'][$method] = [
							'accessible' => true,
							'error' => null,
							'error_description' => null,
							'execution_time_ms' => $executionTime
						];
					}
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
	$tokenType = 'current_user'; // По умолчанию считаем токеном текущего пользователя
	$tokenPreview = substr($authId, 0, 4) . '...' . substr($authId, -4);
	
	$analysis['token_info'] = [
		'exists' => true,
		'length' => strlen($authId),
		'preview' => $tokenPreview,
		'type' => $tokenType
	];
	
	// Получение данных владельца токена через сервисы
	$user = $userService->getCurrentUser($authId, $domain);
	
	if (!$user) {
		$analysis['errors'][] = 'Ошибка получения данных пользователя';
	} else {
		// Получаем ID отдела для получения его названия
		$departmentId = null;
		$departmentName = null;
		
		$userDepartments = $userService->getUserDepartments($user);
		if (!empty($userDepartments)) {
			$departmentId = $userDepartments[0];
			
			if ($departmentId > 0) {
				$departmentData = $apiService->getDepartment($departmentId, $authId, $domain);
				if ($departmentData) {
					$departmentName = $departmentData['NAME'] ?? null;
				}
			}
		}
		
		$analysis['token_owner'] = [
			'id' => $user['ID'] ?? null,
			'name' => $user['NAME'] ?? null,
			'last_name' => $user['LAST_NAME'] ?? null,
			'full_name' => $userService->getUserFullName($user),
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
		$isAdmin = $userService->isAdmin($user, $authId, $domain);
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
		$checkResult = checkApiMethodAccess($method, $authId, $domain);
		$analysis['permissions']['api_methods'][$method] = $checkResult;
	}
	
	// Общее время выполнения анализа
	$analysisExecutionTime = round((microtime(true) - $analysisStartTime) * 1000, 2);
	$analysis['analysis_execution_time_ms'] = $analysisExecutionTime;
	
	return $analysis;
}

// Получение токена из параметров запроса (поддерживаем как GET, так и POST)
// Приоритет: POST (более безопасно), затем GET (для обратной совместимости)
$currentUserAuthId = $_POST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_REQUEST['AUTH_ID'] ?? null;

// Получение домена портала через DomainResolver
$portalDomain = $domainResolver->resolveDomain();
$domainSource = 'resolved'; // DomainResolver определяет источник автоматически

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
	$user = $userService->getCurrentUser($currentUserAuthId, $portalDomain);
	
	if ($user) {
		$isAdmin = $userService->isAdmin($user, $currentUserAuthId, $portalDomain);
	} else {
		$adminCheckError = 'Не удалось получить данные пользователя';
	}
} else {
	// Если нет токена текущего пользователя, пробуем через токен установщика
	$adminCheckResult = CRest::call('user.admin', []);
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
		'user_name' => isset($user) ? $userService->getUserFullName($user) : 'unknown',
		'has_token' => !empty($currentUserAuthId),
		'portal_domain' => $portalDomain,
		'admin_check_error' => $adminCheckError,
		'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
	];
	$logger->log('Token analysis access denied', $accessDeniedLog, 'warning');
	
	// Показываем страницу с ошибкой доступа
	?>
	<!DOCTYPE html>
	<html lang="ru">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Доступ запрещён - Bitrix24 Приложение</title>
		<style>
			* {
				margin: 0;
				padding: 0;
				box-sizing: border-box;
			}
			
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
			opacity: 0;
			animation: fadeIn 0.5s ease-in-out forwards;
		}
		
		@keyframes fadeIn {
			from {
				opacity: 0;
			}
			to {
				opacity: 1;
			}
		}
		
		.error-container {
			background: white;
			border-radius: 16px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			padding: 40px;
			max-width: 500px;
			width: 100%;
			text-align: center;
			opacity: 0;
			transform: scale(0.9) translateY(30px);
			animation: scaleUpFadeIn 0.6s ease-out 0.2s forwards;
		}
		
		@keyframes scaleUpFadeIn {
			from {
				opacity: 0;
				transform: scale(0.9) translateY(30px);
			}
			to {
				opacity: 1;
				transform: scale(1) translateY(0);
			}
		}
			
		.error-icon {
			font-size: 64px;
			margin-bottom: 20px;
			opacity: 0;
			transform: scale(0.5) rotate(-10deg);
			animation: iconBounce 0.6s ease-out 0.4s forwards;
		}
		
		@keyframes iconBounce {
			0% {
				opacity: 0;
				transform: scale(0.5) rotate(-10deg);
			}
			50% {
				transform: scale(1.1) rotate(5deg);
			}
			100% {
				opacity: 1;
				transform: scale(1) rotate(0deg);
			}
		}
		
		.error-title {
			font-size: 28px;
			font-weight: 700;
			color: #333;
			margin-bottom: 15px;
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 0.6s forwards;
		}
		
		.error-message {
			font-size: 16px;
			color: #666;
			margin-bottom: 30px;
			line-height: 1.6;
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 0.8s forwards;
		}
		
		@keyframes fadeInUp {
			from {
				opacity: 0;
				transform: translateY(20px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
			
		.back-button {
			display: inline-block;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 12px 24px;
			border-radius: 8px;
			text-decoration: none;
			font-weight: 600;
			transition: transform 0.3s ease, box-shadow 0.3s ease;
			box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 1s forwards;
		}
		
		.back-button:hover {
			transform: translateY(-3px);
			box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
		}
		</style>
	</head>
	<body>
		<div class="error-container">
			<div class="error-icon">🚫</div>
			<h1 class="error-title">Доступ запрещён</h1>
			<p class="error-message">
				Страница анализа токена доступна только администраторам портала.<br>
				Для доступа к этой странице необходимо иметь права администратора.
			</p>
			<form method="POST" action="index.php" style="display: inline-block;">
				<?php 
				// Получаем токен и домен из любого источника (GET/POST/REQUEST)
				$authIdForForm = $_POST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_REQUEST['AUTH_ID'] ?? null;
				$domainForForm = $_POST['DOMAIN'] ?? $_GET['DOMAIN'] ?? $_REQUEST['DOMAIN'] ?? null;
				?>
				<?php if (!empty($authIdForForm)): ?>
					<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($authIdForForm) ?>">
				<?php endif; ?>
				<?php if (!empty($domainForForm)): ?>
					<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($domainForForm) ?>">
				<?php endif; ?>
				<button type="submit" class="back-button" style="border: none; cursor: pointer;">
					← Вернуться на главную
				</button>
			</form>
		</div>
	</body>
	</html>
	<?php
	exit;
}

// Выполнение анализа токена
$analysisResult = analyzeToken($currentUserAuthId, $portalDomain);

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

$logger->log('Token analysis completed', $logData, 'info');

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Анализ токена - Bitrix24 Приложение</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: #f5f5f5;
			padding: 20px;
			opacity: 0;
			animation: fadeIn 0.5s ease-in-out forwards;
		}
		
		@keyframes fadeIn {
			from {
				opacity: 0;
			}
			to {
				opacity: 1;
			}
		}
		
		.container {
			max-width: 1200px;
			margin: 0 auto;
			background: white;
			border-radius: 8px;
			padding: 30px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			opacity: 0;
			transform: translateY(30px);
			animation: slideUpFadeIn 0.6s ease-out 0.2s forwards;
		}
		
		@keyframes slideUpFadeIn {
			from {
				opacity: 0;
				transform: translateY(30px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
		
		h1 {
			margin-bottom: 20px;
			color: #333;
		}
		
		.json-container {
			position: relative;
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 0.6s forwards;
		}
		
		textarea {
			width: 100%;
			min-height: 500px;
			font-family: 'Courier New', monospace;
			font-size: 14px;
			padding: 15px;
			border: 1px solid #ddd;
			border-radius: 4px;
			resize: vertical;
			background: #f8f9fa;
			transition: border-color 0.3s ease, box-shadow 0.3s ease;
		}
		
		textarea:focus {
			outline: none;
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}
		
		.copy-button {
			position: absolute;
			top: 10px;
			right: 10px;
			background: #667eea;
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
			font-weight: 600;
			transition: all 0.3s ease;
			opacity: 0;
			animation: fadeIn 0.5s ease-out 0.8s forwards;
		}
		
		.copy-button:hover {
			background: #5568d3;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
		}
		
		.copy-button:active {
			background: #4457c2;
			transform: translateY(0);
		}
		
		.success-message {
			display: none;
			position: fixed;
			top: 20px;
			right: 20px;
			background: #28a745;
			color: white;
			padding: 15px 20px;
			border-radius: 4px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.2);
			z-index: 1000;
			animation: slideIn 0.3s ease-out;
		}
		
		@keyframes slideIn {
			from {
				transform: translateX(100%);
				opacity: 0;
			}
			to {
				transform: translateX(0);
				opacity: 1;
			}
		}
		
		.info-box {
			background: #e7f3ff;
			border-left: 4px solid #667eea;
			padding: 15px;
			border-radius: 8px;
			margin-bottom: 20px;
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 0.4s forwards;
		}
		
		@keyframes fadeInUp {
			from {
				opacity: 0;
				transform: translateY(20px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
		
		.info-box p {
			margin: 5px 0;
			font-size: 14px;
			color: #333;
		}
		
		.info-box strong {
			color: #667eea;
		}
	</style>
</head>
<body>
	<div class="container">
		<div style="margin-bottom: 20px; opacity: 0; animation: fadeInLeft 0.5s ease-out 0.3s forwards;">
			<form method="POST" action="index.php" style="display: inline-block;">
				<?php if (!empty($_REQUEST['AUTH_ID'])): ?>
					<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($_REQUEST['AUTH_ID']) ?>">
				<?php endif; ?>
				<?php if (!empty($_REQUEST['DOMAIN'])): ?>
					<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>">
				<?php endif; ?>
				<button type="submit" 
						style="background: transparent; color: #667eea; border: 2px solid #667eea; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">
					← Назад к главной
				</button>
			</form>
		</div>
		
		<style>
			@keyframes fadeInLeft {
				from {
					opacity: 0;
					transform: translateX(-20px);
				}
				to {
					opacity: 1;
					transform: translateX(0);
				}
			}
			
			button[type="submit"]:hover {
				background: #667eea !important;
				color: white !important;
				transform: translateX(-3px);
				box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
			}
		</style>
		
		<h1>Анализ токена Bitrix24</h1>
		
		<div class="info-box">
			<p><strong>Время анализа:</strong> <?= htmlspecialchars($analysisResult['analysis_timestamp']) ?></p>
			<p><strong>Домен портала:</strong> <?= htmlspecialchars($analysisResult['portal_info']['domain']) ?> (источник: <?= htmlspecialchars($analysisResult['portal_info']['domain_source']) ?>)</p>
			<?php if (isset($analysisResult['analysis_execution_time_ms'])): ?>
				<p><strong>Время выполнения:</strong> <?= htmlspecialchars($analysisResult['analysis_execution_time_ms']) ?> мс</p>
			<?php endif; ?>
			<?php if (!empty($analysisResult['errors'])): ?>
				<p><strong>Ошибок:</strong> <?= count($analysisResult['errors']) ?></p>
			<?php endif; ?>
		</div>
		
		<div class="json-container">
			<textarea id="json-output" readonly><?= htmlspecialchars(json_encode($analysisResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
			<button class="copy-button" onclick="copyJson()">Копировать JSON</button>
		</div>
	</div>
	
	<div class="success-message" id="success-message">
		JSON скопирован в буфер обмена!
	</div>
	
	<script>
		function copyJson() {
			const textarea = document.getElementById('json-output');
			textarea.select();
			textarea.setSelectionRange(0, 99999); // Для мобильных устройств
			
			try {
				// Пробуем использовать современный Clipboard API
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(textarea.value).then(function() {
						showSuccessMessage();
					}).catch(function(err) {
						// Fallback на execCommand
						fallbackCopy();
					});
				} else {
					// Fallback для старых браузеров
					fallbackCopy();
				}
			} catch (err) {
				fallbackCopy();
			}
		}
		
		function fallbackCopy() {
			const textarea = document.getElementById('json-output');
			textarea.select();
			textarea.setSelectionRange(0, 99999);
			
			try {
				document.execCommand('copy');
				showSuccessMessage();
			} catch (err) {
				alert('Не удалось скопировать. Пожалуйста, скопируйте вручную (Ctrl+C или Cmd+C).');
			}
		}
		
		function showSuccessMessage() {
			const message = document.getElementById('success-message');
			message.style.display = 'block';
			setTimeout(function() {
				message.style.display = 'none';
			}, 2000);
		}
	</script>
</body>
</html>

