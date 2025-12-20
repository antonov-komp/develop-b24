<?php
/**
 * Функции для работы с правами доступа в приложении Bitrix24
 * 
 * Используется для управления глобальными правами доступа к приложению
 * Документация: https://context7.com/bitrix24/rest/
 */

/**
 * Получение конфигурации прав доступа
 * 
 * @return array Конфигурация прав доступа
 */
function getAccessConfig() {
	$configFile = __DIR__ . '/access-config.json';
	$defaultConfig = [
		'access_control' => [
			'enabled' => true,
			'departments' => [],
			'users' => [],
			'last_updated' => null,
			'updated_by' => null
		]
	];
	
	if (!file_exists($configFile)) {
		return $defaultConfig;
	}
	
	$configContent = @file_get_contents($configFile);
	if ($configContent === false) {
		return $defaultConfig;
	}
	
	$config = @json_decode($configContent, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		return $defaultConfig;
	}
	
	// Убеждаемся, что структура правильная
	if (!isset($config['access_control'])) {
		return $defaultConfig;
	}
	
	return $config;
}

/**
 * Сохранение конфигурации прав доступа
 * 
 * @param array $config Конфигурация для сохранения
 * @return array Результат операции ['success' => bool, 'error' => string|null]
 */
function saveAccessConfig($config) {
	$configFile = __DIR__ . '/access-config.json';
	
	// Убеждаемся, что структура правильная
	if (!isset($config['access_control'])) {
		$error = 'Неверная структура конфигурации: отсутствует секция access_control';
		@file_put_contents(__DIR__ . '/logs/access-config-save-error-' . date('Y-m-d') . '.log', 
			date('Y-m-d H:i:s') . ' - STRUCTURE ERROR: ' . $error . "\nConfig keys: " . implode(', ', array_keys($config)) . "\n", 
			FILE_APPEND);
		return ['success' => false, 'error' => $error];
	}
	
	// Убеждаемся, что все необходимые поля инициализированы
	if (!isset($config['access_control']['departments']) || !is_array($config['access_control']['departments'])) {
		$config['access_control']['departments'] = [];
	}
	if (!isset($config['access_control']['users']) || !is_array($config['access_control']['users'])) {
		$config['access_control']['users'] = [];
	}
	
	$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	
	if ($json === false) {
		$error = 'Ошибка кодирования JSON: ' . json_last_error_msg();
		@file_put_contents(__DIR__ . '/logs/access-config-save-error-' . date('Y-m-d') . '.log', 
			date('Y-m-d H:i:s') . ' - JSON ENCODE ERROR: ' . $error . "\nConfig structure: " . print_r($config, true) . "\n", 
			FILE_APPEND);
		return ['success' => false, 'error' => $error];
	}
	
	// Проверяем права на запись
	if (!is_writable($configFile) && file_exists($configFile)) {
		$error = 'Файл конфигурации недоступен для записи. Проверьте права доступа.';
		@file_put_contents(__DIR__ . '/logs/access-config-save-error-' . date('Y-m-d') . '.log', 
			date('Y-m-d H:i:s') . ' - FILE NOT WRITABLE: ' . $configFile . ' (owner: ' . fileowner($configFile) . ', perms: ' . substr(sprintf('%o', fileperms($configFile)), -4) . ')' . "\n", 
			FILE_APPEND);
		return ['success' => false, 'error' => $error];
	}
	
	// Проверяем права на директорию, если файл не существует
	if (!file_exists($configFile)) {
		$dir = dirname($configFile);
		if (!is_writable($dir)) {
			$error = 'Директория недоступна для записи. Проверьте права доступа.';
			@file_put_contents(__DIR__ . '/logs/access-config-save-error-' . date('Y-m-d') . '.log', 
				date('Y-m-d H:i:s') . ' - DIRECTORY NOT WRITABLE: ' . $dir . "\n", 
				FILE_APPEND);
			return ['success' => false, 'error' => $error];
		}
	}
	
	// Пробуем сохранить файл
	// Сначала пробуем с блокировкой
	$result = @file_put_contents($configFile, $json, LOCK_EX);
	
	// Если не получилось, пробуем без блокировки
	if ($result === false) {
		$result = @file_put_contents($configFile, $json);
	}
	
	if ($result === false) {
		$error = 'Ошибка записи файла. Проверьте права доступа и место на диске.';
		$lastError = error_get_last();
		if ($lastError) {
			$error .= ' (' . $lastError['message'] . ')';
		}
		
		// Детальное логирование ошибки
		$errorLog = [
			'timestamp' => date('Y-m-d H:i:s'),
			'error' => $error,
			'file' => $configFile,
			'file_exists' => file_exists($configFile),
			'is_writable' => is_writable($configFile),
			'is_dir_writable' => is_writable(dirname($configFile)),
			'file_perms' => file_exists($configFile) ? substr(sprintf('%o', fileperms($configFile)), -4) : 'N/A',
			'file_owner' => file_exists($configFile) ? fileowner($configFile) : 'N/A',
			'file_group' => file_exists($configFile) ? filegroup($configFile) : 'N/A',
			'current_user' => get_current_user(),
			'process_user' => posix_getpwuid(posix_geteuid())['name'] ?? 'unknown',
			'disk_free_space' => disk_free_space(dirname($configFile)),
			'json_length' => strlen($json),
			'last_error' => $lastError
		];
		
		@file_put_contents(__DIR__ . '/logs/access-config-save-error-' . date('Y-m-d') . '.log', 
			date('Y-m-d H:i:s') . ' - FILE WRITE ERROR: ' . json_encode($errorLog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", 
			FILE_APPEND);
		
		return ['success' => false, 'error' => $error];
	}
	
	// Логируем успешное сохранение для отладки
	@file_put_contents(__DIR__ . '/logs/access-config-save-success-' . date('Y-m-d') . '.log', 
		date('Y-m-d H:i:s') . ' - FILE SAVED SUCCESSFULLY: ' . $configFile . ' (bytes: ' . $result . ')' . "\n", 
		FILE_APPEND);
	
	return ['success' => true, 'error' => null];
}

/**
 * Добавление отдела в список доступа
 * 
 * @param int $departmentId ID отдела
 * @param string $departmentName Название отдела
 * @param array $addedBy Информация о том, кто добавил ['id' => int, 'name' => string]
 * @return array Результат операции ['success' => bool, 'error' => string|null]
 */
function addDepartmentToAccess($departmentId, $departmentName, $addedBy) {
	// Валидация входных данных
	if (empty($departmentId) || $departmentId <= 0) {
		return ['success' => false, 'error' => 'Не указан ID отдела'];
	}
	
	if (empty($departmentName)) {
		return ['success' => false, 'error' => 'Не указано название отдела'];
	}
	
	$config = getAccessConfig();
	
	// Инициализируем массив отделов, если его нет
	if (!isset($config['access_control']['departments']) || !is_array($config['access_control']['departments'])) {
		$config['access_control']['departments'] = [];
	}
	
	// Проверяем, нет ли уже такого отдела
	foreach ($config['access_control']['departments'] as $dept) {
		if (isset($dept['id']) && (int)$dept['id'] == (int)$departmentId) {
			return ['success' => false, 'error' => 'Отдел уже есть в списке доступа'];
		}
	}
	
	$config['access_control']['departments'][] = [
		'id' => (int)$departmentId,
		'name' => trim($departmentName),
		'added_at' => date('Y-m-d H:i:s'),
		'added_by' => $addedBy
	];
	
	$config['access_control']['last_updated'] = date('Y-m-d H:i:s');
	$config['access_control']['updated_by'] = $addedBy;
	
	$saveResult = saveAccessConfig($config);
	if ($saveResult['success']) {
		return ['success' => true, 'error' => null];
	} else {
		return ['success' => false, 'error' => $saveResult['error'] ?? 'Ошибка при сохранении конфигурации'];
	}
}

/**
 * Удаление отдела из списка доступа
 * 
 * @param int $departmentId ID отдела
 * @return bool true если успешно
 */
function removeDepartmentFromAccess($departmentId) {
	$config = getAccessConfig();
	
	$departments = $config['access_control']['departments'] ?? [];
	$newDepartments = [];
	
	foreach ($departments as $dept) {
		if (isset($dept['id']) && $dept['id'] != $departmentId) {
			$newDepartments[] = $dept;
		}
	}
	
	$config['access_control']['departments'] = $newDepartments;
	$config['access_control']['last_updated'] = date('Y-m-d H:i:s');
	
	$saveResult = saveAccessConfig($config);
	return $saveResult['success'];
}

/**
 * Добавление пользователя в список доступа
 * 
 * @param int $userId ID пользователя
 * @param string $userName ФИО пользователя
 * @param string|null $userEmail Email пользователя
 * @param array $addedBy Информация о том, кто добавил ['id' => int, 'name' => string]
 * @return array Результат операции ['success' => bool, 'error' => string|null]
 */
function addUserToAccess($userId, $userName, $userEmail, $addedBy) {
	// Валидация входных данных
	if (empty($userId) || $userId <= 0) {
		return ['success' => false, 'error' => 'Не указан ID пользователя'];
	}
	
	if (empty($userName)) {
		return ['success' => false, 'error' => 'Не указано имя пользователя'];
	}
	
	$config = getAccessConfig();
	
	// Инициализируем массив пользователей, если его нет
	if (!isset($config['access_control']['users']) || !is_array($config['access_control']['users'])) {
		$config['access_control']['users'] = [];
	}
	
	// Проверяем, нет ли уже такого пользователя
	foreach ($config['access_control']['users'] as $user) {
		if (isset($user['id']) && (int)$user['id'] == (int)$userId) {
			return ['success' => false, 'error' => 'Пользователь уже есть в списке доступа'];
		}
	}
	
	$config['access_control']['users'][] = [
		'id' => (int)$userId,
		'name' => trim($userName),
		'email' => $userEmail ? trim($userEmail) : null,
		'added_at' => date('Y-m-d H:i:s'),
		'added_by' => $addedBy
	];
	
	$config['access_control']['last_updated'] = date('Y-m-d H:i:s');
	$config['access_control']['updated_by'] = $addedBy;
	
	// Логирование перед сохранением для отладки
	$usersArray = $config['access_control']['users'] ?? [];
	$lastUser = !empty($usersArray) ? end($usersArray) : null;
	@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
		date('Y-m-d H:i:s') . ' - addUserToAccess BEFORE SAVE: ' . json_encode([
			'user_id' => $userId,
			'user_name' => $userName,
			'config_structure' => [
				'has_access_control' => isset($config['access_control']),
				'users_count' => count($usersArray),
				'last_user' => $lastUser
			]
		], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n", 
		FILE_APPEND);
	
	$saveResult = saveAccessConfig($config);
	
	// Логирование результата сохранения
	@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
		date('Y-m-d H:i:s') . ' - addUserToAccess SAVE RESULT: ' . json_encode($saveResult, JSON_UNESCAPED_UNICODE) . "\n", 
		FILE_APPEND);
	
	if ($saveResult['success']) {
		return ['success' => true, 'error' => null];
	} else {
		return ['success' => false, 'error' => $saveResult['error'] ?? 'Ошибка при сохранении конфигурации'];
	}
}

/**
 * Удаление пользователя из списка доступа
 * 
 * @param int $userId ID пользователя
 * @return bool true если успешно
 */
function removeUserFromAccess($userId) {
	$config = getAccessConfig();
	
	$users = $config['access_control']['users'] ?? [];
	$newUsers = [];
	
	foreach ($users as $user) {
		if (isset($user['id']) && $user['id'] != $userId) {
			$newUsers[] = $user;
		}
	}
	
	$config['access_control']['users'] = $newUsers;
	$config['access_control']['last_updated'] = date('Y-m-d H:i:s');
	
	$saveResult = saveAccessConfig($config);
	return $saveResult['success'];
}

/**
 * Включение/выключение проверки прав доступа
 * 
 * @param bool $enabled Включить или выключить
 * @param array $updatedBy Информация о том, кто изменил ['id' => int, 'name' => string]
 * @return array Результат операции ['success' => bool, 'error' => string|null]
 */
function toggleAccessControl($enabled, $updatedBy) {
	$config = getAccessConfig();
	
	$config['access_control']['enabled'] = (bool)$enabled;
	$config['access_control']['last_updated'] = date('Y-m-d H:i:s');
	$config['access_control']['updated_by'] = $updatedBy;
	
	return saveAccessConfig($config);
}

/**
 * Получение списка всех отделов портала
 * 
 * Метод: department.get (без параметра ID) или department.list
 * Документация: https://context7.com/bitrix24/rest/department.get
 * 
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return array Массив отделов [['id' => 1, 'name' => 'Отдел продаж'], ...]
 */
function getAllDepartments($authId, $domain) {
	if (empty($authId) || empty($domain)) {
		return [];
	}
	
	// Пробуем метод department.get без параметра ID
	$url = 'https://' . $domain . '/rest/department.get.json';
	$params = http_build_query(['auth' => $authId]);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);
	
	$result = null;
	
	if ($curlError || $httpCode !== 200) {
		// Пробуем через CRest (токен установщика)
		require_once(__DIR__ . '/crest.php');
		$result = CRest::call('department.get', []);
		
		if (isset($result['error'])) {
			// Если и это не работает, пробуем department.list
			$result = CRest::call('department.list', []);
		}
	} else {
		$result = json_decode($response, true);
	}
	
	if (isset($result['error']) || !isset($result['result'])) {
		return [];
	}
	
	$departments = [];
	$resultData = $result['result'];
	
	// Обработка разных вариантов структуры ответа
	if (is_array($resultData)) {
		foreach ($resultData as $dept) {
			if (is_array($dept) && isset($dept['ID'])) {
				$departments[] = [
					'id' => (int)$dept['ID'],
					'name' => $dept['NAME'] ?? 'Без названия'
				];
			}
		}
	}
	
	return $departments;
}

/**
 * Получение списка всех пользователей портала
 * 
 * Метод: user.get (без параметра ID)
 * Документация: https://context7.com/bitrix24/rest/user.get
 * 
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @param string|null $search Поисковый запрос (имя или email)
 * @return array Массив пользователей [['id' => 1, 'name' => 'Иван Иванов', 'email' => 'ivan@example.com'], ...]
 */
function getAllUsers($authId, $domain, $search = null) {
	if (empty($authId) || empty($domain)) {
		return [];
	}
	
	$url = 'https://' . $domain . '/rest/user.get.json';
	
	$requestParams = ['auth' => $authId];
	
	// Если есть поисковый запрос — добавляем фильтр
	if ($search) {
		$requestParams['filter'] = [
			'NAME' => '%' . $search . '%',
			'EMAIL' => '%' . $search . '%'
		];
	}
	
	$params = http_build_query($requestParams);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);
	
	$result = null;
	
	if ($curlError || $httpCode !== 200) {
		// Пробуем через CRest (токен установщика)
		require_once(__DIR__ . '/crest.php');
		$result = CRest::call('user.get', $search ? ['filter' => $requestParams['filter']] : []);
	} else {
		$result = json_decode($response, true);
	}
	
	if (isset($result['error']) || !isset($result['result'])) {
		return [];
	}
	
	$users = [];
	$resultData = $result['result'];
	
	// Обработка структуры ответа
	if (is_array($resultData)) {
		foreach ($resultData as $user) {
			if (is_array($user) && isset($user['ID'])) {
				$users[] = [
					'id' => (int)$user['ID'],
					'name' => trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? '')),
					'email' => $user['EMAIL'] ?? null
				];
			}
		}
	}
	
	return $users;
}

/**
 * Проверка, является ли пользователь администратором
 * 
 * Использует логику из index.php и token-analysis.php
 * 
 * @param array $user Данные пользователя
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return bool true если администратор
 */
function checkIsAdmin($user, $authId, $domain) {
	if (!isset($user['ID'])) {
		return false;
	}
	
	// Проверяем поле ADMIN в данных пользователя
	if (isset($user['ADMIN'])) {
		$adminValue = $user['ADMIN'];
		return (
			$adminValue === 'Y' || 
			$adminValue === 'y' || 
			$adminValue == 1 || 
			$adminValue === 1 || 
			$adminValue === true ||
			$adminValue === '1'
		);
	}
	
	// Проверяем альтернативное поле IS_ADMIN
	if (isset($user['IS_ADMIN'])) {
		return ($user['IS_ADMIN'] === 'Y' || $user['IS_ADMIN'] == 1 || $user['IS_ADMIN'] === true);
	}
	
	// Если поле ADMIN отсутствует, используем метод user.admin
	$url = 'https://' . $domain . '/rest/user.admin.json';
	$params = http_build_query(['auth' => $authId]);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	if ($httpCode === 200) {
		$result = json_decode($response, true);
		if (isset($result['result'])) {
			return ($result['result'] === true || $result['result'] === 'true' || $result['result'] == 1);
		}
	}
	
	// Fallback: через CRest
	require_once(__DIR__ . '/crest.php');
	$adminCheckResult = CRest::call('user.admin', []);
	if (isset($adminCheckResult['result'])) {
		return ($adminCheckResult['result'] === true || $adminCheckResult['result'] === 'true' || $adminCheckResult['result'] == 1);
	}
	
	return false;
}

/**
 * Получение данных текущего пользователя через токен из запроса
 * 
 * Дублируем функцию из index.php для избежания циклических зависимостей
 * 
 * @param string $authId Токен текущего пользователя из $_REQUEST['AUTH_ID']
 * @param string $domain Домен портала
 * @return array|null Данные пользователя или null при ошибке
 */
function getCurrentUserDataForAccess($authId, $domain) {
	if (empty($authId) || empty($domain)) {
		return null;
	}
	
	// Метод: user.current
	// Документация: https://context7.com/bitrix24/rest/user.current
	$url = 'https://' . $domain . '/rest/user.current.json';
	
	$requestParams = [
		'auth' => $authId
	];
	
	$params = http_build_query($requestParams);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);
	
	if ($curlError) {
		return ['error' => 'curl_error', 'error_description' => $curlError];
	}
	
	if ($httpCode !== 200) {
		$result = json_decode($response, true);
		if (isset($result['error'])) {
			return $result;
		}
		return ['error' => 'http_error', 'error_description' => 'HTTP Code: ' . $httpCode];
	}
	
	$result = json_decode($response, true);
	
	if (json_last_error() !== JSON_ERROR_NONE) {
		return ['error' => 'json_error', 'error_description' => json_last_error_msg()];
	}
	
	return $result;
}

/**
 * Проверка прав доступа пользователя к приложению
 * 
 * @param int $userId ID пользователя
 * @param array $userDepartments Массив ID отделов пользователя
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return bool true если доступ разрешён, false если запрещён
 */
function checkUserAccess($userId, $userDepartments, $authId, $domain) {
	// Получаем данные пользователя для проверки статуса администратора
	$userResult = getCurrentUserDataForAccess($authId, $domain);
	
	if (isset($userResult['error']) || !isset($userResult['result'])) {
		// Не удалось получить данные пользователя — запрещаем доступ
		logAccessCheck($userId, $userDepartments, 'denied', 'user_data_error');
		return false;
	}
	
	$user = $userResult['result'];
	
	// Проверка статуса администратора
	$isAdmin = checkIsAdmin($user, $authId, $domain);
	if ($isAdmin) {
		// Администраторы всегда имеют доступ
		logAccessCheck($userId, $userDepartments, 'granted', 'admin');
		return true;
	}
	
	// Чтение конфигурации прав доступа
	$accessConfig = getAccessConfig();
	
	// Если проверка отключена — доступ разрешён
	if (!isset($accessConfig['access_control']['enabled']) || !$accessConfig['access_control']['enabled']) {
		logAccessCheck($userId, $userDepartments, 'granted', 'check_disabled');
		return true;
	}
	
	$departments = $accessConfig['access_control']['departments'] ?? [];
	$users = $accessConfig['access_control']['users'] ?? [];
	
	// Если списки пустые — доступ запрещён
	if (empty($departments) && empty($users)) {
		logAccessCheck($userId, $userDepartments, 'denied', 'no_access_rules');
		return false;
	}
	
	// Проверка, есть ли отдел пользователя в списке
	if (!empty($userDepartments) && is_array($userDepartments)) {
		foreach ($userDepartments as $deptId) {
			foreach ($departments as $dept) {
				if (isset($dept['id']) && $dept['id'] == $deptId) {
					logAccessCheck($userId, $userDepartments, 'granted', 'department_in_list');
					return true;
				}
			}
		}
	}
	
	// Проверка, есть ли пользователь в списке
	foreach ($users as $user) {
		if (isset($user['id']) && $user['id'] == $userId) {
			logAccessCheck($userId, $userDepartments, 'granted', 'user_in_list');
			return true;
		}
	}
	
	// Доступ запрещён
	logAccessCheck($userId, $userDepartments, 'denied', 'not_in_lists');
	return false;
}

/**
 * Логирование проверки прав доступа
 * 
 * @param int $userId ID пользователя
 * @param array $userDepartments Массив ID отделов пользователя
 * @param string $result Результат проверки ('granted' или 'denied')
 * @param string $reason Причина (admin, department_in_list, user_in_list, not_in_lists и т.д.)
 */
function logAccessCheck($userId, $userDepartments, $result, $reason) {
	$logFile = __DIR__ . '/logs/access-check-' . date('Y-m-d') . '.log';
	$logEntry = [
		'timestamp' => date('Y-m-d H:i:s'),
		'user_id' => $userId,
		'user_departments' => $userDepartments,
		'result' => $result,
		'reason' => $reason
	];
	
	@file_put_contents($logFile, 
		date('Y-m-d H:i:s') . ' - ' . json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", 
		FILE_APPEND);
}

/**
 * Логирование операций управления правами доступа
 * 
 * @param string $operation Тип операции (add_department, remove_department, add_user, remove_user, toggle_enabled)
 * @param array $data Данные операции
 * @param array $performedBy Информация о том, кто выполнил операцию ['id' => int, 'name' => string]
 * @param bool $success Успешность операции
 */
function logAccessControlOperation($operation, $data, $performedBy, $success) {
	$logFile = __DIR__ . '/logs/access-control-' . date('Y-m-d') . '.log';
	$logEntry = [
		'timestamp' => date('Y-m-d H:i:s'),
		'operation' => $operation,
		'data' => $data,
		'performed_by' => $performedBy,
		'success' => $success
	];
	
	@file_put_contents($logFile, 
		date('Y-m-d H:i:s') . ' - ' . json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", 
		FILE_APPEND);
}

