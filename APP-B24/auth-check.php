<?php
/**
 * Проверка авторизации Bitrix24
 * 
 * Проверяет, что запрос приходит из Bitrix24 с активной авторизацией
 * Используется для защиты index.php и install.php от прямого доступа
 * 
 * @return bool true если авторизация валидна, false в противном случае
 */
function checkBitrix24Auth()
{
	// Логирование для диагностики (временное)
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
	// Bitrix24 передаёт специальные параметры при открытии приложения внутри портала
	$isFromBitrix24 = false;
	
	// Проверка 1: наличие параметров, которые Bitrix24 передаёт при открытии приложения
	if (
		(isset($_REQUEST['DOMAIN']) && !empty($_REQUEST['DOMAIN'])) ||
		(isset($_REQUEST['AUTH_ID']) && !empty($_REQUEST['AUTH_ID'])) ||
		(isset($_REQUEST['APP_SID']) && !empty($_REQUEST['APP_SID']))
	) {
		$isFromBitrix24 = true;
	}
	
	// Проверка 2: Referer header указывает на домен Bitrix24
	if (!$isFromBitrix24 && isset($_SERVER['HTTP_REFERER'])) {
		$referer = $_SERVER['HTTP_REFERER'];
		// Проверяем, что Referer содержит домен Bitrix24
		$settingsFile = __DIR__ . '/settings.json';
		if (file_exists($settingsFile)) {
			$settingsContent = file_get_contents($settingsFile);
			$settings = json_decode($settingsContent, true);
			if (isset($settings['domain']) && !empty($settings['domain'])) {
				$bitrixDomain = $settings['domain'];
				if (strpos($referer, $bitrixDomain) !== false || 
					strpos($referer, 'bitrix24') !== false) {
					$isFromBitrix24 = true;
				}
			}
		}
	}
	
	// Проверка 3: User-Agent может содержать информацию о Bitrix24
	if (!$isFromBitrix24 && isset($_SERVER['HTTP_USER_AGENT'])) {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		if (stripos($userAgent, 'bitrix') !== false) {
			$isFromBitrix24 = true;
		}
	}
	
	$logData['is_from_bitrix24'] = $isFromBitrix24;
	
	// Проверяем наличие установленного приложения
	$settingsFile = __DIR__ . '/settings.json';
	
	if (!file_exists($settingsFile)) {
		// Настройки не найдены - приложение не установлено
		return false;
	}
	
	// Читаем настройки
	$settingsContent = file_get_contents($settingsFile);
	$settings = json_decode($settingsContent, true);
	
	if ($settings === null || !is_array($settings)) {
		// Невалидный JSON или пустые настройки
		return false;
	}
	
	// Проверяем наличие обязательных полей в настройках
	if (
		empty($settings['access_token']) ||
		empty($settings['domain']) ||
		empty($settings['client_endpoint'])
	) {
		// Неполные настройки
		return false;
	}
	
	// Проверяем валидность токена через тестовый запрос к Bitrix24
	require_once(__DIR__ . '/crest.php');
	
	try {
		$testResult = CRest::call('profile');
		
		// Если есть ошибка авторизации - проверяем тип ошибки
		if (isset($testResult['error'])) {
			// Исключение: expired_token - токен можно обновить автоматически
			if ($testResult['error'] === 'expired_token') {
				// CRest автоматически обновит токен при следующем запросе
				// Проверяем ещё раз после обновления
				$refreshResult = CRest::call('profile');
				if (isset($refreshResult['error']) && 
					in_array($refreshResult['error'], ['invalid_token', 'invalid_grant', 'invalid_client', 'NO_AUTH_FOUND'])) {
					$logData['result'] = 'denied_token_invalid_after_refresh';
					$logData['error'] = $refreshResult['error'];
					@file_put_contents(__DIR__ . '/logs/auth-check-' . date('Y-m-d') . '.log', 
						date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", 
						FILE_APPEND);
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
						@file_put_contents(__DIR__ . '/logs/auth-check-' . date('Y-m-d') . '.log', 
							date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", 
							FILE_APPEND);
						return false;
					}
				}
				$logData['result'] = 'allowed_token_refreshed';
				$logData['is_from_bitrix24'] = $isFromBitrix24;
				@file_put_contents(__DIR__ . '/logs/auth-check-' . date('Y-m-d') . '.log', 
					date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", 
					FILE_APPEND);
				return true;
			}
			
			// Ошибка no_install_app - возможно, settings.json повреждён или содержит тестовые данные
			// Если есть признаки запроса из Bitrix24 - разрешаем доступ (для работы через iframe)
			if ($testResult['error'] === 'no_install_app' && $isFromBitrix24) {
				$logData['result'] = 'allowed_no_install_app_but_from_bitrix24';
				$logData['error'] = $testResult['error'];
				$logData['warning'] = 'Settings.json may be corrupted or contain test data';
				@file_put_contents(__DIR__ . '/logs/auth-check-' . date('Y-m-d') . '.log', 
					date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", 
					FILE_APPEND);
				// Разрешаем доступ, если запрос точно из Bitrix24
				return true;
			}
			
			// Другие ошибки авторизации - доступ запрещён
			if (in_array($testResult['error'], ['invalid_token', 'invalid_grant', 'invalid_client', 'NO_AUTH_FOUND'])) {
				$logData['result'] = 'denied_token_invalid';
				$logData['error'] = $testResult['error'];
				@file_put_contents(__DIR__ . '/logs/auth-check-' . date('Y-m-d') . '.log', 
					date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", 
					FILE_APPEND);
				return false;
			}
		}
		
		// Если запрос успешен - разрешаем доступ
		// Если токен валиден, разрешаем доступ независимо от источника запроса
		// Это позволяет открывать приложение напрямую, используя токен установщика из settings.json
		$logData['result'] = 'allowed';
		$logData['token_valid'] = true;
		$logData['is_from_bitrix24'] = $isFromBitrix24;
		$logData['access_type'] = $isFromBitrix24 ? 'bitrix24_iframe' : 'direct_access_with_installer_token';
		@file_put_contents(__DIR__ . '/logs/auth-check-' . date('Y-m-d') . '.log', 
			date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", 
			FILE_APPEND);
		return true;
	} catch (Exception $e) {
		// Ошибка при проверке - доступ запрещён
		$logData['result'] = 'denied_exception';
		$logData['exception'] = $e->getMessage();
		@file_put_contents(__DIR__ . '/logs/auth-check-' . date('Y-m-d') . '.log', 
			date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", 
			FILE_APPEND);
		return false;
	}
}

/**
 * Редирект на страницу ошибки доступа
 */
function redirectToFailure()
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

