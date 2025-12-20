<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только внутри Bitrix24 при активной авторизации
 * Управляется через конфигурационный файл config.json
 * Отображает приветствие с информацией о текущем пользователе
 * Документация: https://context7.com/bitrix24/rest/
 */

require_once(__DIR__ . '/auth-check.php');

// Подключение и инициализация сервисов
require_once(__DIR__ . '/src/bootstrap.php');

/**
 * Показ страницы ошибки конфига
 * 
 * @param string $message Сообщение для пользователя
 * @param string|null $lastUpdated Дата последнего обновления конфига
 */
function showConfigErrorPage($message, $lastUpdated = null) {
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
	
	// Очищаем буфер вывода перед отправкой заголовков
	if (ob_get_level()) {
		ob_clean();
	}
	
	// Отправляем заголовки редиректа
	header('HTTP/1.1 503 Service Unavailable', true, 503);
	header('Location: ' . $errorUrl, true, 302);
	header('Content-Type: text/html; charset=UTF-8');
	
	// Выводим сообщение на случай, если редирект не сработает
	echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($errorUrl) . '"></head><body><p>Redirecting to <a href="' . htmlspecialchars($errorUrl) . '">error page</a>...</p></body></html>';
	
	exit;
}

// Проверка авторизации Bitrix24 (уже выполнена в auth-check.php)
// Проверка, идет ли запрос из Bitrix24 (через iframe)
$isFromBitrix24 = $authService->isRequestFromBitrix24();

// Проверка конфигурации доступа к главной странице
// ВАЖНО: Если запрос идет из Bitrix24 (iframe) - всегда разрешаем доступ, проверка конфига не нужна
// Если запрос прямой (прямой URL) - проверяем конфиг, и если enabled: true, то разрешаем
if (!$isFromBitrix24) {
	// Прямой доступ - проверяем конфиг
	$indexConfig = $configService->getIndexPageConfig();
	if (!$indexConfig['enabled']) {
		$logger->logConfigCheck('CONFIG CHECK FAILED: enabled=false, redirecting to config-error.php (direct access)');
		showConfigErrorPage(
			$indexConfig['message'] ?? 'Интерфейс приложения временно недоступен.',
			$indexConfig['last_updated'] ?? null
		);
		exit;
	}
	$logger->logConfigCheck('CONFIG CHECK PASSED: enabled=true (direct access allowed)');
} else {
	// Запрос из Bitrix24 - всегда разрешаем, проверка конфига не нужна
	$logger->logConfigCheck('CONFIG CHECK SKIPPED: Request from Bitrix24 iframe, always allowed');
}

// Проверка прав доступа (если включена)
$accessConfig = $configService->getAccessConfig();
if ($accessConfig['access_control']['enabled']) {
	// Получаем данные текущего пользователя
	$currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;
	$portalDomain = $domainResolver->resolveDomain();
	
	if ($currentUserAuthId && $portalDomain && $portalDomain !== 'oauth.bitrix.info') {
		// Получаем данные пользователя
		$user = $userService->getCurrentUser($currentUserAuthId, $portalDomain);
		
		if ($user && isset($user['ID'])) {
			$userId = $user['ID'];
			$userDepartments = $userService->getUserDepartments($user);
			
			// Проверяем, является ли пользователь администратором
			$isAdmin = $userService->isAdmin($user, $currentUserAuthId, $portalDomain);
			
			// Если не администратор — проверяем права доступа
			if (!$isAdmin) {
				$hasAccess = $accessControlService->checkUserAccess($userId, $userDepartments, $currentUserAuthId, $portalDomain);
				
				if (!$hasAccess) {
					// Доступ запрещён — редирект на failure.php
					$logger->logConfigCheck('ACCESS DENIED: User does not have access rights');
					$authService->redirectToFailure();
					exit;
				}
			}
		}
	}
}

$logger->logConfigCheck('ACCESS GRANTED: Auth and config checks passed, showing interface');

// Подключаем CREST для работы с Bitrix24 API
require_once(__DIR__ . '/crest.php');
$logger->logConfigCheck('CREST loaded successfully');

// Получение токена текущего пользователя из параметров запроса
$currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;

// Логирование для отладки
$debugLog = [
	'has_auth_id' => !empty($currentUserAuthId),
	'auth_id_length' => $currentUserAuthId ? strlen($currentUserAuthId) : 0,
	'request_params' => array_keys($_REQUEST),
	'timestamp' => date('Y-m-d H:i:s')
];
$logger->log('User check', $debugLog, 'info');

// Получение домена портала через DomainResolver
$portalDomain = $domainResolver->resolveDomain();

// Получение данных текущего пользователя
$user = null;
$isCurrentUserToken = false; // Флаг: используется ли токен текущего пользователя

if ($currentUserAuthId && $portalDomain) {
	// Используем токен текущего пользователя для получения его данных
	$isCurrentUserToken = true;
	$user = $userService->getCurrentUser($currentUserAuthId, $portalDomain);
	
	if (!$user) {
		die('<h1>Ошибка получения данных пользователя</h1><p>Не удалось получить данные пользователя через API</p>');
	}
	
	// Если поле ADMIN отсутствует, делаем дополнительный запрос через user.get
	// Метод: user.get
	// Документация: https://context7.com/bitrix24/rest/user.get
	if (!isset($user['ADMIN']) && isset($user['ID'])) {
		$userId = $user['ID'];
		$fullUser = $userService->getUserById($userId, $currentUserAuthId, $portalDomain);
		
		if ($fullUser) {
			// Объединяем данные, приоритет у данных из user.get (там есть ADMIN)
			$user = array_merge($user, $fullUser);
		}
	}
} else {
	// Fallback: если нет токена текущего пользователя, используем токен установщика
	// (но это будет владелец токена, а не текущий пользователь)
	$isCurrentUserToken = false;
	$userResult = CRest::call('user.current', []);
	
	if (isset($userResult['error'])) {
		$errorMessage = $userResult['error_description'] ?? $userResult['error'];
		die('<h1>Ошибка получения данных пользователя</h1><p>' . htmlspecialchars($errorMessage) . '</p>');
	}
	
	$user = $userResult['result'] ?? null;
	
	// Для токена установщика тоже пытаемся получить ADMIN через user.get
	if ($user && !isset($user['ADMIN']) && isset($user['ID'])) {
		$userId = $user['ID'];
		$getUserResult = CRest::call('user.get', [
			'id' => $userId,
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
	$portalDomain = $domainResolver->resolveDomain();
	
	// Если все еще не определен, используем значение по умолчанию
	if (!$portalDomain) {
		$portalDomain = 'не указан';
		$logger->logConfigCheck('WARNING: Portal domain not found, using default');
	}
}

// Формирование данных пользователя
$userFullName = $userService->getUserFullName($user);

// Проверка статуса администратора через UserService
$isAdmin = $userService->isAdmin($user, $currentUserAuthId ?? '', $portalDomain ?? '');

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
$logger->log('Admin check', $adminDebugLog, 'info');

$adminStatus = $isAdmin ? 'Администратор на портале' : 'Пользователь';

// Фото пользователя (если есть)
$userPhoto = $user['PERSONAL_PHOTO'] ?? null;

// Получение данных об отделе пользователя
$departmentId = null;
$departmentName = null;

// Получаем ID отдела из поля UF_DEPARTMENT (массив ID отделов)
$userDepartments = $userService->getUserDepartments($user);

if (!empty($userDepartments)) {
	// Логирование для отладки
	$deptDebugLog = [
		'user_id' => $user['ID'] ?? 'unknown',
		'uf_department_exists' => isset($user['UF_DEPARTMENT']),
		'uf_department_type' => isset($user['UF_DEPARTMENT']) ? gettype($user['UF_DEPARTMENT']) : 'not_set',
		'uf_department_value' => $user['UF_DEPARTMENT'] ?? 'not_set',
		'timestamp' => date('Y-m-d H:i:s')
	];
	$logger->log('Department check', $deptDebugLog, 'info');
	
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
			$departmentData = $apiService->getDepartment($departmentId, $currentUserAuthId ?? '', $portalDomain ?? '');
			
			if ($departmentData) {
				$departmentName = $departmentData['NAME'] ?? null;
			}
		} catch (\Exception $e) {
			// Игнорируем ошибки - просто не получим название отдела
			// Будет показан только ID
		}
	}
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Приветствие - Bitrix24 Приложение</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
		
		.welcome-container {
			background: white;
			border-radius: 16px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			padding: 40px;
			max-width: 600px;
			width: 100%;
			text-align: center;
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
		
		.welcome-header {
			margin-bottom: 30px;
		}
		
		.welcome-title {
			font-size: 32px;
			font-weight: 700;
			color: #333;
			margin-bottom: 10px;
		}
		
		.user-photo {
			width: 120px;
			height: 120px;
			border-radius: 50%;
			margin: 0 auto 20px;
			object-fit: cover;
			border: 4px solid #667eea;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
			opacity: 0;
			transform: scale(0.8);
			animation: scaleIn 0.5s ease-out 0.4s forwards;
		}
		
		@keyframes scaleIn {
			from {
				opacity: 0;
				transform: scale(0.8);
			}
			to {
				opacity: 1;
				transform: scale(1);
			}
		}
		
		.user-name {
			font-size: 28px;
			font-weight: 600;
			color: #333;
			margin-bottom: 15px;
		}
		
		.user-info {
			background: #f8f9fa;
			border-radius: 12px;
			padding: 25px;
			margin-bottom: 20px;
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 0.6s forwards;
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
		
		.info-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 12px 0;
			border-bottom: 1px solid #e9ecef;
		}
		
		.info-row:last-child {
			border-bottom: none;
		}
		
		.info-label {
			font-weight: 600;
			color: #666;
			font-size: 14px;
		}
		
		.info-value {
			font-weight: 500;
			color: #333;
			font-size: 16px;
		}
		
		.admin-badge {
			display: inline-block;
			background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
			color: white;
			padding: 8px 16px;
			border-radius: 20px;
			font-size: 14px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
		}
		
		.user-badge {
			display: inline-block;
			background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
			color: white;
			padding: 8px 16px;
			border-radius: 20px;
			font-size: 14px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
		}
		
		.domain-info {
			background: #e7f3ff;
			border-left: 4px solid #667eea;
			padding: 15px;
			border-radius: 8px;
			margin-top: 20px;
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 0.8s forwards;
		}
		
		.domain-label {
			font-size: 12px;
			color: #666;
			margin-bottom: 5px;
		}
		
		.domain-value {
			font-size: 18px;
			font-weight: 600;
			color: #667eea;
		}
		
		.footer {
			margin-top: 30px;
			padding-top: 20px;
			border-top: 1px solid #e9ecef;
			font-size: 12px;
			color: #999;
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 1s forwards;
		}
		
		.footer form {
			opacity: 0;
			animation: fadeInUp 0.5s ease-out 1.2s forwards;
		}
		
		.footer button {
			transition: transform 0.3s ease, box-shadow 0.3s ease;
		}
		
		.footer button:hover {
			transform: translateY(-3px);
			box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
		}
	</style>
</head>
<body>
	<div class="welcome-container">
		<div class="welcome-header">
			<h1 class="welcome-title">Добро пожаловать!</h1>
			<?php if ($userPhoto): ?>
				<img src="<?= htmlspecialchars($userPhoto) ?>" alt="Фото пользователя" class="user-photo">
			<?php endif; ?>
			<div class="user-name"><?= htmlspecialchars($userFullName) ?></div>
		</div>
		
		<div class="user-info">
			<div class="info-row">
				<span class="info-label">ID пользователя:</span>
				<span class="info-value">#<?= htmlspecialchars($user['ID']) ?></span>
			</div>
			
			<div class="info-row">
				<span class="info-label">Статус:</span>
				<span class="info-value">
					<?php if ($isAdmin): ?>
						<span class="admin-badge"><?= htmlspecialchars($adminStatus) ?></span>
					<?php else: ?>
						<span class="user-badge"><?= htmlspecialchars($adminStatus) ?></span>
					<?php endif; ?>
				</span>
			</div>
			
			<?php 
			// Временный блок отладки - можно удалить после проверки
			$debugMode = isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1';
			if ($debugMode): 
			?>
			<div class="info-row" style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 15px; flex-direction: column; align-items: flex-start;">
				<strong style="margin-bottom: 10px; color: #856404;">Отладочная информация:</strong>
				<div style="font-size: 12px; color: #856404; text-align: left; width: 100%;">
					<p><strong>ADMIN поле:</strong> <?= isset($user['ADMIN']) ? var_export($user['ADMIN'], true) : 'не установлено' ?></p>
					<p><strong>Тип ADMIN:</strong> <?= isset($user['ADMIN']) ? gettype($user['ADMIN']) : 'не установлено' ?></p>
					<p><strong>Результат проверки:</strong> <?= $isAdmin ? 'ДА (администратор)' : 'НЕТ (пользователь)' ?></p>
					<p><strong>UF_DEPARTMENT:</strong> <?= isset($user['UF_DEPARTMENT']) ? var_export($user['UF_DEPARTMENT'], true) : 'не установлено' ?></p>
					<p><strong>ID отдела:</strong> <?= $departmentId ? $departmentId : 'не найден' ?></p>
					<p><strong>Название отдела:</strong> <?= $departmentName ? $departmentName : 'не получено' ?></p>
					<p><strong>Все поля пользователя:</strong></p>
					<pre style="background: white; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; max-height: 200px; overflow-y: auto;"><?= htmlspecialchars(print_r($user, true)) ?></pre>
				</div>
			</div>
			<?php endif; ?>
			
			<?php if (isset($user['EMAIL']) && !empty($user['EMAIL'])): ?>
			<div class="info-row">
				<span class="info-label">Email:</span>
				<span class="info-value"><?= htmlspecialchars($user['EMAIL']) ?></span>
			</div>
			<?php endif; ?>
			
			<?php if ($departmentId): ?>
			<div class="info-row">
				<span class="info-label">Отдел:</span>
				<span class="info-value">
					<?php if ($departmentName): ?>
						<?= htmlspecialchars($departmentName) ?> (ID: <?= htmlspecialchars($departmentId) ?>)
					<?php else: ?>
						ID: <?= htmlspecialchars($departmentId) ?>
					<?php endif; ?>
				</span>
			</div>
			<?php endif; ?>
			
			<?php if (isset($user['TIME_ZONE']) && !empty($user['TIME_ZONE'])): ?>
			<div class="info-row">
				<span class="info-label">Часовой пояс:</span>
				<span class="info-value"><?= htmlspecialchars($user['TIME_ZONE']) ?></span>
			</div>
			<?php endif; ?>
		</div>
		
		<div class="domain-info">
			<div class="domain-label">Домен портала:</div>
			<div class="domain-value"><?= htmlspecialchars($portalDomain) ?></div>
		</div>
		
		<div class="footer">
			<p>Приложение успешно авторизовано и готово к работе</p>
			<?php if (!$isCurrentUserToken): ?>
				<p style="color: #f5576c; margin-top: 10px; font-size: 11px;">
					⚠️ Используется токен установщика (владельца приложения). 
					Токен текущего пользователя не найден в параметрах запроса.
				</p>
			<?php else: ?>
				<p style="color: #28a745; margin-top: 10px; font-size: 11px;">
					✓ Используется токен текущего пользователя
				</p>
			<?php endif; ?>
			
			<?php if ($isAdmin): ?>
			<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef; display: flex; gap: 15px; flex-wrap: wrap;">
				<form method="POST" action="token-analysis.php" style="display: inline-block;">
					<?php if (!empty($_REQUEST['AUTH_ID'])): ?>
						<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($_REQUEST['AUTH_ID']) ?>">
					<?php endif; ?>
					<?php if (!empty($_REQUEST['DOMAIN'])): ?>
						<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>">
					<?php endif; ?>
					<button type="submit" 
							style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); font-size: 14px;">
						🔍 Анализ токена и прав доступа
					</button>
				</form>
				<form method="POST" action="access-control.php" style="display: inline-block;">
					<?php if (!empty($_REQUEST['AUTH_ID'])): ?>
						<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($_REQUEST['AUTH_ID']) ?>">
					<?php endif; ?>
					<?php if (!empty($_REQUEST['DOMAIN'])): ?>
						<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>">
					<?php endif; ?>
					<button type="submit" 
							style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3); font-size: 14px;">
						🔐 Управление правами доступа
					</button>
				</form>
			</div>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>