<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только внутри Bitrix24 при активной авторизации
 * Отображает приветствие с информацией о текущем пользователе
 * Документация: https://context7.com/bitrix24/rest/
 */

require_once(__DIR__ . '/auth-check.php');

// Проверка авторизации Bitrix24
if (!checkBitrix24Auth()) {
	redirectToFailure();
}

require_once(__DIR__ . '/crest.php');

/**
 * Получение данных текущего пользователя через токен из запроса
 * 
 * Bitrix24 передает AUTH_ID в параметрах запроса - это токен текущего пользователя
 * Используем его для получения данных того, кто открыл приложение
 * 
 * @param string $authId Токен текущего пользователя из $_REQUEST['AUTH_ID']
 * @param string $domain Домен портала
 * @return array|null Данные пользователя или null при ошибке
 */
function getCurrentUserData($authId, $domain) {
	if (empty($authId) || empty($domain)) {
		return null;
	}
	
	// Явно запрашиваем поле ADMIN для проверки статуса администратора
	// Метод: user.current
	// Документация: https://context7.com/bitrix24/rest/user.current
	$url = 'https://' . $domain . '/rest/user.current.json';
	
	// Формируем параметры - сначала пробуем без select (получим все поля по умолчанию)
	// Если нужны конкретные поля, можно добавить select позже
	$requestParams = [
		'auth' => $authId
	];
	
	$params = http_build_query($requestParams);
	
	// Логирование запроса для отладки
	$requestLog = [
		'url' => $url,
		'params' => $params,
		'auth_length' => strlen($authId),
		'timestamp' => date('Y-m-d H:i:s')
	];
	@file_put_contents(__DIR__ . '/logs/user-current-api-' . date('Y-m-d') . '.log', 
		date('Y-m-d H:i:s') . ' - REQUEST: ' . json_encode($requestLog, JSON_UNESCAPED_UNICODE) . "\n", 
		FILE_APPEND);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	$responsePreview = substr($response, 0, 500);
	curl_close($ch);
	
	// Логирование ответа
	$responseLog = [
		'http_code' => $httpCode,
		'curl_error' => $curlError ?: 'none',
		'response_preview' => $responsePreview,
		'timestamp' => date('Y-m-d H:i:s')
	];
	@file_put_contents(__DIR__ . '/logs/user-current-api-' . date('Y-m-d') . '.log', 
		date('Y-m-d H:i:s') . ' - RESPONSE: ' . json_encode($responseLog, JSON_UNESCAPED_UNICODE) . "\n", 
		FILE_APPEND);
	
	if ($curlError) {
		return ['error' => 'curl_error', 'error_description' => $curlError];
	}
	
	if ($httpCode !== 200) {
		// Для 404 проверяем, может быть это ошибка API, а не HTTP
		$result = json_decode($response, true);
		if (isset($result['error'])) {
			return $result; // Возвращаем ошибку API
		}
		return ['error' => 'http_error', 'error_description' => 'HTTP Code: ' . $httpCode . '. Response: ' . $responsePreview];
	}
	
	$result = json_decode($response, true);
	
	if (json_last_error() !== JSON_ERROR_NONE) {
		return ['error' => 'json_error', 'error_description' => json_last_error_msg()];
	}
	
	return $result;
}

/**
 * Получение данных отдела по ID
 * 
 * Метод: department.get
 * Документация: https://context7.com/bitrix24/rest/department.get
 * 
 * @param int $departmentId ID отдела
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return array|null Данные отдела или null при ошибке
 */
function getDepartmentData($departmentId, $authId, $domain) {
	if (empty($departmentId) || empty($authId) || empty($domain)) {
		return null;
	}
	
	$url = 'https://' . $domain . '/rest/department.get.json';
	// Пробуем разные форматы параметров
	$params = http_build_query([
		'auth' => $authId,
		'ID' => $departmentId  // Используем ID вместо id (может быть чувствительно к регистру)
	]);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);
	
	// Логирование для отладки
	$deptApiLog = [
		'department_id' => $departmentId,
		'url' => $url,
		'http_code' => $httpCode,
		'curl_error' => $curlError ?: 'none',
		'response_preview' => substr($response, 0, 500), // Первые 500 символов ответа
		'timestamp' => date('Y-m-d H:i:s')
	];
	@file_put_contents(__DIR__ . '/logs/department-api-' . date('Y-m-d') . '.log', 
		date('Y-m-d H:i:s') . ' - ' . json_encode($deptApiLog, JSON_UNESCAPED_UNICODE) . "\n", 
		FILE_APPEND);
	
	if ($curlError) {
		return null;
	}
	
	if ($httpCode !== 200) {
		return null;
	}
	
	$result = json_decode($response, true);
	
	if (json_last_error() !== JSON_ERROR_NONE) {
		return null;
	}
	
	// Проверяем наличие ошибки в ответе
	if (isset($result['error'])) {
		$deptErrorLog = [
			'department_id' => $departmentId,
			'error' => $result['error'],
			'error_description' => $result['error_description'] ?? 'no description',
			'timestamp' => date('Y-m-d H:i:s')
		];
		@file_put_contents(__DIR__ . '/logs/department-api-' . date('Y-m-d') . '.log', 
			date('Y-m-d H:i:s') . ' - ERROR: ' . json_encode($deptErrorLog, JSON_UNESCAPED_UNICODE) . "\n", 
			FILE_APPEND);
		return null;
	}
	
	// Метод department.get возвращает массив отделов в result
	// Проверяем разные варианты структуры ответа
	if (isset($result['result']) && is_array($result['result'])) {
		// Если result - массив отделов
		if (isset($result['result'][0]) && is_array($result['result'][0])) {
			return $result['result'][0];
		}
		// Если result - один отдел (не массив)
		if (isset($result['result']['ID']) || isset($result['result']['NAME'])) {
			return $result['result'];
		}
	}
	
	return null;
}

// Получение токена текущего пользователя из параметров запроса
$currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;

// Логирование для отладки (можно отключить в продакшене)
$debugLog = [
	'has_auth_id' => !empty($currentUserAuthId),
	'auth_id_length' => $currentUserAuthId ? strlen($currentUserAuthId) : 0,
	'request_params' => array_keys($_REQUEST),
	'timestamp' => date('Y-m-d H:i:s')
];
@file_put_contents(__DIR__ . '/logs/user-check-' . date('Y-m-d') . '.log', 
	date('Y-m-d H:i:s') . ' - ' . json_encode($debugLog, JSON_UNESCAPED_UNICODE) . "\n", 
	FILE_APPEND);

// Получение домена портала
$settingsFile = __DIR__ . '/settings.json';
$portalDomain = null;

// Приоритет 1: Домен из параметров запроса (самый надежный)
if (isset($_REQUEST['DOMAIN']) && !empty($_REQUEST['DOMAIN'])) {
	$portalDomain = $_REQUEST['DOMAIN'];
}

// Приоритет 2: Домен из client_endpoint в settings.json
if (!$portalDomain && file_exists($settingsFile)) {
	$settingsContent = file_get_contents($settingsFile);
	$settings = json_decode($settingsContent, true);
	if (isset($settings['client_endpoint']) && !empty($settings['client_endpoint'])) {
		// Извлекаем домен из client_endpoint (например, https://develop.bitrix24.by/rest/)
		$clientEndpoint = $settings['client_endpoint'];
		if (preg_match('#https?://([^/]+)#', $clientEndpoint, $matches)) {
			$portalDomain = $matches[1];
		}
	}
}

// Приоритет 3: Домен из settings.json (но проверяем, что это не oauth.bitrix.info)
if (!$portalDomain && file_exists($settingsFile)) {
	$settingsContent = file_get_contents($settingsFile);
	$settings = json_decode($settingsContent, true);
	if (isset($settings['domain']) && !empty($settings['domain'])) {
		$domainFromSettings = $settings['domain'];
		// Игнорируем oauth.bitrix.info - это не домен портала
		if ($domainFromSettings !== 'oauth.bitrix.info') {
			$portalDomain = $domainFromSettings;
		}
	}
}

// Получение данных текущего пользователя
$user = null;
$userResult = null;
$isCurrentUserToken = false; // Флаг: используется ли токен текущего пользователя

if ($currentUserAuthId && $portalDomain) {
	// Используем токен текущего пользователя для получения его данных
	$isCurrentUserToken = true;
	$userResult = getCurrentUserData($currentUserAuthId, $portalDomain);
	
	if (isset($userResult['error'])) {
		$errorMessage = $userResult['error_description'] ?? $userResult['error'];
		die('<h1>Ошибка получения данных пользователя</h1><p>' . htmlspecialchars($errorMessage) . '</p>');
	}
	
	$user = $userResult['result'] ?? null;
	
	// Если поле ADMIN отсутствует, делаем дополнительный запрос через user.get
	// Метод: user.get
	// Документация: https://context7.com/bitrix24/rest/user.get
	if ($user && !isset($user['ADMIN']) && isset($user['ID'])) {
		$userId = $user['ID'];
		$getUserUrl = 'https://' . $portalDomain . '/rest/user.get.json';
			$getUserParams = http_build_query([
				'auth' => $currentUserAuthId,
				'id' => $userId,
				'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL', 'ADMIN', 'PERSONAL_PHOTO', 'TIME_ZONE', 'UF_DEPARTMENT']
			]);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $getUserUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $getUserParams);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
		
		$getUserResponse = curl_exec($ch);
		$getUserHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if ($getUserHttpCode === 200) {
			$getUserResult = json_decode($getUserResponse, true);
			if (isset($getUserResult['result'][0]) && is_array($getUserResult['result'][0])) {
				// Объединяем данные, приоритет у данных из user.get (там есть ADMIN)
				$user = array_merge($user, $getUserResult['result'][0]);
			}
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
	$portalDomain = 'не указан';
}

// Формирование данных пользователя
$userName = $user['NAME'] ?? '';
$userLastName = $user['LAST_NAME'] ?? '';
$userFullName = trim($userName . ' ' . $userLastName);
if (empty($userFullName)) {
	$userFullName = 'Пользователь #' . ($user['ID'] ?? 'неизвестен');
}

// Проверка статуса администратора
// В Bitrix24 поле ADMIN может быть: 'Y' (да), 'N' (нет), 1 (да), true (да), или отсутствовать
$isAdmin = false;

// Сначала проверяем поле ADMIN в данных пользователя
if (isset($user['ADMIN'])) {
	$adminValue = $user['ADMIN'];
	// Проверяем различные варианты значения
	$isAdmin = (
		$adminValue === 'Y' || 
		$adminValue === 'y' || 
		$adminValue == 1 || 
		$adminValue === 1 || 
		$adminValue === true ||
		$adminValue === '1'
	);
} else {
	// Если поле ADMIN отсутствует, проверяем альтернативные поля
	// В некоторых версиях Bitrix24 может быть поле IS_ADMIN или другие варианты
	if (isset($user['IS_ADMIN'])) {
		$isAdmin = ($user['IS_ADMIN'] === 'Y' || $user['IS_ADMIN'] == 1 || $user['IS_ADMIN'] === true);
	} else {
		// Если поле ADMIN все еще отсутствует, используем метод user.admin для проверки
		// Метод: user.admin
		// Документация: https://context7.com/bitrix24/rest/user.admin
		$adminCheckResult = null;
		
		if ($isCurrentUserToken && $currentUserAuthId && $portalDomain) {
			// Проверка через токен текущего пользователя
			$adminCheckUrl = 'https://' . $portalDomain . '/rest/user.admin.json';
			$adminCheckParams = http_build_query(['auth' => $currentUserAuthId]);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $adminCheckUrl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $adminCheckParams);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
			
			$adminCheckResponse = curl_exec($ch);
			$adminCheckHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if ($adminCheckHttpCode === 200) {
				$adminCheckResult = json_decode($adminCheckResponse, true);
			}
		} else {
			// Проверка через токен установщика
			$adminCheckResult = CRest::call('user.admin', []);
		}
		
		// Метод user.admin возвращает true/false в поле result
		if (isset($adminCheckResult['result'])) {
			$isAdmin = ($adminCheckResult['result'] === true || $adminCheckResult['result'] === 'true' || $adminCheckResult['result'] == 1);
		}
	}
}

// Логирование для отладки (можно отключить в продакшене)
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
	'all_user_fields' => array_keys($user), // Все ключи для отладки
	'timestamp' => date('Y-m-d H:i:s')
];
@file_put_contents(__DIR__ . '/logs/admin-check-' . date('Y-m-d') . '.log', 
	date('Y-m-d H:i:s') . ' - ' . json_encode($adminDebugLog, JSON_UNESCAPED_UNICODE) . "\n", 
	FILE_APPEND);

$adminStatus = $isAdmin ? 'Администратор на портале' : 'Пользователь';

// Фото пользователя (если есть)
$userPhoto = $user['PERSONAL_PHOTO'] ?? null;

// Получение данных об отделе пользователя
$departmentId = null;
$departmentName = null;
$departmentData = null;

// Получаем ID отдела из поля UF_DEPARTMENT (массив ID отделов)
if (isset($user['UF_DEPARTMENT'])) {
	// Логирование для отладки
	$deptDebugLog = [
		'user_id' => $user['ID'] ?? 'unknown',
		'uf_department_exists' => isset($user['UF_DEPARTMENT']),
		'uf_department_type' => isset($user['UF_DEPARTMENT']) ? gettype($user['UF_DEPARTMENT']) : 'not_set',
		'uf_department_value' => $user['UF_DEPARTMENT'] ?? 'not_set',
		'timestamp' => date('Y-m-d H:i:s')
	];
	@file_put_contents(__DIR__ . '/logs/department-check-' . date('Y-m-d') . '.log', 
		date('Y-m-d H:i:s') . ' - ' . json_encode($deptDebugLog, JSON_UNESCAPED_UNICODE) . "\n", 
		FILE_APPEND);
	
	if (is_array($user['UF_DEPARTMENT']) && !empty($user['UF_DEPARTMENT'])) {
		// Берем первый отдел (основной отдел пользователя)
		$departmentId = (int)$user['UF_DEPARTMENT'][0];
		
		// Получаем данные отдела через API
		// ВАЖНО: Токен может не иметь прав на department.get
		// Пробуем получить название, но если ошибка - просто показываем ID
		if ($departmentId > 0) {
			// Пробуем получить название отдела через токен установщика (CRest)
			// Метод: department.get
			// Документация: https://context7.com/bitrix24/rest/department.get
			try {
				$deptResult = CRest::call('department.get', ['ID' => $departmentId]);
				
				// Проверяем наличие ошибки
				if (!isset($deptResult['error']) && isset($deptResult['result'])) {
					// Обрабатываем разные варианты структуры ответа
					if (is_array($deptResult['result'])) {
						// Если result - массив отделов
						if (isset($deptResult['result'][0]) && is_array($deptResult['result'][0])) {
							$departmentData = $deptResult['result'][0];
						} 
						// Если result - один отдел (ассоциативный массив)
						elseif (isset($deptResult['result']['ID']) || isset($deptResult['result']['NAME'])) {
							$departmentData = $deptResult['result'];
						}
						// Если result - массив с ключом по ID
						elseif (isset($deptResult['result'][$departmentId])) {
							$departmentData = $deptResult['result'][$departmentId];
						}
					}
					
					if (isset($departmentData) && is_array($departmentData)) {
						$departmentName = $departmentData['NAME'] ?? null;
					}
				}
			} catch (Exception $e) {
				// Игнорируем ошибки - просто не получим название отдела
				// Будет показан только ID
			}
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
		}
		
		.welcome-container {
			background: white;
			border-radius: 16px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			padding: 40px;
			max-width: 600px;
			width: 100%;
			text-align: center;
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
		</div>
	</div>
</body>
</html>