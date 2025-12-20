<?php
/**
 * Страница управления правами доступа в приложении Bitrix24
 * 
 * Позволяет администраторам настраивать, кто имеет доступ к приложению
 * Документация: https://context7.com/bitrix24/rest/
 */

require_once(__DIR__ . '/auth-check.php');

// Подключение и инициализация сервисов
require_once(__DIR__ . '/src/bootstrap.php');

require_once(__DIR__ . '/crest.php');

// Получение данных текущего пользователя
$currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;
$portalDomain = $domainResolver->resolveDomain();

// Получение данных пользователя для проверки администратора
$user = null;
$isAdmin = false;

if ($currentUserAuthId && $portalDomain) {
	$user = $userService->getCurrentUser($currentUserAuthId, $portalDomain);
	
	if ($user) {
		$isAdmin = $userService->isAdmin($user, $currentUserAuthId, $portalDomain);
	}
}

// Если пользователь не администратор - показываем ошибку доступа
if (!$isAdmin) {
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
			}
			
			.error-container {
				background: white;
				border-radius: 16px;
				box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
				padding: 40px;
				max-width: 500px;
				width: 100%;
				text-align: center;
			}
			
			.error-title {
				font-size: 28px;
				font-weight: 700;
				color: #333;
				margin-bottom: 15px;
			}
			
			.error-message {
				font-size: 16px;
				color: #666;
				margin-bottom: 30px;
				line-height: 1.6;
			}
			
			.back-button {
				display: inline-block;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: white;
				padding: 12px 24px;
				border-radius: 8px;
				text-decoration: none;
				font-weight: 600;
				border: none;
				cursor: pointer;
			}
		</style>
	</head>
	<body>
		<div class="error-container">
			<div style="font-size: 64px; margin-bottom: 20px;">🚫</div>
			<h1 class="error-title">Доступ запрещён</h1>
			<p class="error-message">
				Страница управления правами доступа доступна только администраторам портала.
			</p>
			<form method="POST" action="index.php" style="display: inline-block;">
				<?php if (!empty($currentUserAuthId)): ?>
					<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($currentUserAuthId) ?>">
				<?php endif; ?>
				<?php if (!empty($portalDomain)): ?>
					<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($portalDomain) ?>">
				<?php endif; ?>
				<button type="submit" class="back-button">← Вернуться на главную</button>
			</form>
		</div>
	</body>
	</html>
	<?php
	exit;
}

// Обработка POST-запросов
$message = null;
$messageType = null; // 'success' или 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? null;
	$performedBy = [
		'id' => $user['ID'] ?? 0,
		'name' => trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? ''))
	];
	
	if (empty($performedBy['name'])) {
		$performedBy['name'] = 'Пользователь #' . ($user['ID'] ?? 'неизвестен');
	}
	
	switch ($action) {
		case 'toggle_enabled':
			$enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';
			$result = $accessControlService->toggleAccessControl($enabled, $performedBy);
			
			if ($result['success']) {
				$logger->logAccessControl('toggle_enabled', ['enabled' => $enabled, 'performed_by' => $performedBy, 'success' => true]);
				$message = $enabled ? 'Проверка прав доступа включена' : 'Проверка прав доступа выключена';
				$messageType = 'success';
			} else {
				$logger->logAccessControl('toggle_enabled', ['enabled' => $enabled, 'error' => $result['error'] ?? 'unknown', 'performed_by' => $performedBy, 'success' => false]);
				$message = $result['error'] ?? 'Ошибка при изменении настройки';
				$messageType = 'error';
			}
			break;
			
		case 'add_department':
			$departmentId = (int)($_POST['department_id'] ?? 0);
			$departmentName = trim($_POST['department_name'] ?? '');
			
			if ($departmentId > 0 && !empty($departmentName)) {
				$result = $accessControlService->addDepartment($departmentId, $departmentName, $performedBy);
				
				if ($result['success']) {
					$logger->logAccessControl('add_department', ['id' => $departmentId, 'name' => $departmentName, 'performed_by' => $performedBy, 'success' => true]);
					$message = 'Отдел добавлен в список доступа';
					$messageType = 'success';
				} else {
					$logger->logAccessControl('add_department', ['id' => $departmentId, 'name' => $departmentName, 'error' => $result['error'] ?? 'unknown', 'performed_by' => $performedBy, 'success' => false]);
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
				if ($accessControlService->removeDepartment($departmentId)) {
					$logger->logAccessControl('remove_department', ['id' => $departmentId, 'performed_by' => $performedBy, 'success' => true]);
					$message = 'Отдел удалён из списка доступа';
					$messageType = 'success';
				} else {
					$logger->logAccessControl('remove_department', ['id' => $departmentId, 'performed_by' => $performedBy, 'success' => false]);
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
					$result = $accessControlService->addUser($userId, $userName, $userEmail, $performedBy);
					
					if ($result['success']) {
						$logger->logAccessControl('add_user', ['id' => $userId, 'name' => $userName, 'email' => $userEmail, 'performed_by' => $performedBy, 'success' => true]);
						$message = 'Пользователь добавлен в список доступа';
						$messageType = 'success';
					} else {
						$logger->logAccessControl('add_user', ['id' => $userId, 'name' => $userName, 'email' => $userEmail, 'error' => $result['error'] ?? 'unknown', 'performed_by' => $performedBy, 'success' => false]);
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
				$logger->logError('Error adding user to access control', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
				$message = 'Ошибка при добавлении пользователя: ' . $e->getMessage();
				$messageType = 'error';
			} catch (\Error $e) {
				$logger->logError('Critical error adding user to access control', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
				$message = 'Критическая ошибка при добавлении пользователя: ' . $e->getMessage();
				$messageType = 'error';
			}
			break;
			
		case 'remove_user':
			$userId = (int)($_POST['user_id'] ?? 0);
			
			if ($userId > 0) {
				if ($accessControlService->removeUser($userId)) {
					$logger->logAccessControl('remove_user', ['id' => $userId, 'performed_by' => $performedBy, 'success' => true]);
					$message = 'Пользователь удалён из списка доступа';
					$messageType = 'success';
				} else {
					$logger->logAccessControl('remove_user', ['id' => $userId, 'performed_by' => $performedBy, 'success' => false]);
					$message = 'Ошибка при удалении пользователя';
					$messageType = 'error';
				}
			}
			break;
	}
	
	// После успешного сохранения делаем редирект на GET-запрос с сохранением параметров
	// Это предотвращает повторную отправку формы при обновлении страницы
	if ($messageType === 'success' && isset($_POST['action'])) {
		try {
			@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
				date('Y-m-d H:i:s') . ' - REDIRECT START: messageType=' . $messageType . ', action=' . ($_POST['action'] ?? 'none') . "\n", 
				FILE_APPEND);
			
			// Получаем параметры из POST или REQUEST
			$authId = $_POST['AUTH_ID'] ?? $_REQUEST['AUTH_ID'] ?? $currentUserAuthId ?? '';
			$domain = $_POST['DOMAIN'] ?? $_REQUEST['DOMAIN'] ?? $portalDomain ?? '';
			
			// Формируем URL редиректа - используем SCRIPT_NAME для правильного пути
			$redirectUrl = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '/APP-B24/access-control.php';
			
			$params = [];
			
			if (!empty($authId)) {
				$params['AUTH_ID'] = $authId;
			}
			if (!empty($domain)) {
				$params['DOMAIN'] = $domain;
			}
			
			$params['success'] = '1';
			$params['action'] = $_POST['action'];
			
			$redirectUrl .= '?' . http_build_query($params);
			
			// Логирование редиректа для отладки
			@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
				date('Y-m-d H:i:s') . ' - REDIRECT: ' . json_encode([
					'redirect_url' => $redirectUrl,
					'auth_id' => $authId,
					'domain' => $domain,
					'action' => $_POST['action'],
					'php_self' => $_SERVER['PHP_SELF']
				], JSON_UNESCAPED_UNICODE) . "\n", 
				FILE_APPEND);
			
			// Очищаем все буферы вывода перед отправкой заголовков
			@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
				date('Y-m-d H:i:s') . ' - REDIRECT: Clearing output buffers, level=' . ob_get_level() . "\n", 
				FILE_APPEND);
			
			while (ob_get_level()) {
				ob_end_clean();
			}
			
			@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
				date('Y-m-d H:i:s') . ' - REDIRECT: Sending header Location: ' . $redirectUrl . "\n", 
				FILE_APPEND);
			
			// Редирект с сохранением сообщения через GET-параметр
			header('Location: ' . $redirectUrl, true, 303);
			exit;
		} catch (Exception $e) {
			@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
				date('Y-m-d H:i:s') . ' - REDIRECT EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n", 
				FILE_APPEND);
			// Не делаем редирект при ошибке, просто показываем сообщение
		} catch (Error $e) {
			@file_put_contents(__DIR__ . '/logs/access-control-debug-' . date('Y-m-d') . '.log', 
				date('Y-m-d H:i:s') . ' - REDIRECT ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n", 
				FILE_APPEND);
			// Не делаем редирект при ошибке, просто показываем сообщение
		}
	}
}

// Получение текущей конфигурации
$accessConfig = $configService->getAccessConfig();

// Проверка успешного сохранения через GET-параметр
if (isset($_GET['success']) && isset($_GET['action'])) {
	$action = $_GET['action'] ?? '';
	switch ($action) {
		case 'add_user':
			$message = 'Пользователь добавлен в список доступа';
			$messageType = 'success';
			break;
		case 'add_department':
			$message = 'Отдел добавлен в список доступа';
			$messageType = 'success';
			break;
		case 'remove_user':
			$message = 'Пользователь удалён из списка доступа';
			$messageType = 'success';
			break;
		case 'remove_department':
			$message = 'Отдел удалён из списка доступа';
			$messageType = 'success';
			break;
		case 'toggle_enabled':
			$message = 'Настройки сохранены';
			$messageType = 'success';
			break;
	}
}

// Получение списка всех отделов для выпадающего списка
$allDepartments = [];
if ($currentUserAuthId && $portalDomain) {
	$allDepartments = $apiService->getAllDepartments($currentUserAuthId, $portalDomain);
}

// Получение списка всех пользователей для выпадающего списка
$allUsers = [];
if ($currentUserAuthId && $portalDomain) {
	$allUsers = $apiService->getAllUsers($currentUserAuthId, $portalDomain);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Управление правами доступа - Bitrix24 Приложение</title>
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
			padding: 20px;
		}
		
		.container {
			max-width: 1200px;
			margin: 0 auto;
			background: white;
			border-radius: 16px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			padding: 40px;
		}
		
		h1 {
			font-size: 32px;
			font-weight: 700;
			color: #333;
			margin-bottom: 30px;
		}
		
		.message {
			padding: 15px 20px;
			border-radius: 8px;
			margin-bottom: 20px;
			font-weight: 500;
		}
		
		.message.success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}
		
		.message.error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}
		
		.section {
			margin-bottom: 40px;
			padding: 25px;
			background: #f8f9fa;
			border-radius: 12px;
		}
		
		.section h2 {
			font-size: 24px;
			font-weight: 600;
			color: #333;
			margin-bottom: 20px;
		}
		
		.toggle-section {
			display: flex;
			align-items: center;
			gap: 15px;
			margin-bottom: 30px;
		}
		
		.toggle-section label {
			font-size: 18px;
			font-weight: 500;
			color: #333;
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		
		.toggle-section input[type="checkbox"] {
			width: 24px;
			height: 24px;
			cursor: pointer;
		}
		
		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
			background: white;
			border-radius: 8px;
			overflow: hidden;
		}
		
		th, td {
			padding: 12px;
			text-align: left;
			border-bottom: 1px solid #e9ecef;
		}
		
		th {
			background: #667eea;
			color: white;
			font-weight: 600;
		}
		
		tr:last-child td {
			border-bottom: none;
		}
		
		.btn {
			padding: 10px 20px;
			border: none;
			border-radius: 8px;
			font-size: 14px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
		}
		
		.btn-primary {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
		}
		
		.btn-primary:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
		}
		
		.btn-danger {
			background: #dc3545;
			color: white;
		}
		
		.btn-danger:hover {
			background: #c82333;
		}
		
		select, input[type="text"] {
			width: 100%;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 8px;
			font-size: 14px;
			margin-bottom: 15px;
		}
		
		.add-form {
			display: flex;
			gap: 10px;
			align-items: flex-end;
		}
		
		.add-form select,
		.add-form input[type="text"] {
			margin-bottom: 0;
		}
		
		.footer {
			margin-top: 40px;
			padding-top: 20px;
			border-top: 1px solid #e9ecef;
			display: flex;
			gap: 15px;
		}
		
		.empty-state {
			text-align: center;
			padding: 40px;
			color: #666;
			font-size: 16px;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>Управление правами доступа</h1>
		
		<?php if ($message): ?>
			<div class="message <?= $messageType ?>">
				<?= htmlspecialchars($message) ?>
			</div>
		<?php endif; ?>
		
		<div class="section">
			<div class="toggle-section">
				<form method="POST" style="display: flex; align-items: center; gap: 15px;">
					<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($currentUserAuthId ?? '') ?>">
					<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($portalDomain ?? '') ?>">
					<input type="hidden" name="action" value="toggle_enabled">
					<label>
						<input type="checkbox" name="enabled" value="1" 
							<?= $accessConfig['access_control']['enabled'] ? 'checked' : '' ?>
							onchange="this.form.submit()">
						Включить проверку прав доступа
					</label>
				</form>
			</div>
		</div>
		
		<div class="section">
			<h2>Отделы с доступом</h2>
			
			<?php if (!empty($accessConfig['access_control']['departments'])): ?>
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Название</th>
							<th>Добавлен</th>
							<th>Кто добавил</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($accessConfig['access_control']['departments'] as $dept): ?>
							<tr>
								<td><?= htmlspecialchars($dept['id'] ?? '') ?></td>
								<td><?= htmlspecialchars($dept['name'] ?? '') ?></td>
								<td><?= htmlspecialchars($dept['added_at'] ?? '') ?></td>
								<td><?= htmlspecialchars($dept['added_by']['name'] ?? '') ?></td>
								<td>
									<form method="POST" style="display: inline-block;">
										<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($currentUserAuthId ?? '') ?>">
										<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($portalDomain ?? '') ?>">
										<input type="hidden" name="action" value="remove_department">
										<input type="hidden" name="department_id" value="<?= htmlspecialchars($dept['id'] ?? '') ?>">
										<button type="submit" class="btn btn-danger" 
											onclick="return confirm('Вы уверены, что хотите удалить этот отдел из списка доступа?')">
											Удалить
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				<div class="empty-state">Нет отделов с доступом</div>
			<?php endif; ?>
			
			<?php if (!empty($allDepartments)): ?>
				<form method="POST" class="add-form">
					<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($currentUserAuthId ?? '') ?>">
					<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($portalDomain ?? '') ?>">
					<input type="hidden" name="action" value="add_department">
					<select name="department_id" id="department-select" required>
						<option value="">Выберите отдел</option>
						<?php foreach ($allDepartments as $dept): ?>
							<option value="<?= htmlspecialchars($dept['id']) ?>" 
								data-name="<?= htmlspecialchars($dept['name']) ?>">
								<?= htmlspecialchars($dept['name']) ?> (ID: <?= htmlspecialchars($dept['id']) ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<input type="hidden" name="department_name" id="department-name">
					<button type="submit" class="btn btn-primary">Добавить отдел</button>
				</form>
			<?php endif; ?>
		</div>
		
		<div class="section">
			<h2>Пользователи с доступом</h2>
			
			<?php if (!empty($accessConfig['access_control']['users'])): ?>
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>ФИО</th>
							<th>Email</th>
							<th>Добавлен</th>
							<th>Кто добавил</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($accessConfig['access_control']['users'] as $usr): ?>
							<tr>
								<td><?= htmlspecialchars($usr['id'] ?? '') ?></td>
								<td><?= htmlspecialchars($usr['name'] ?? '') ?></td>
								<td><?= htmlspecialchars($usr['email'] ?? '') ?></td>
								<td><?= htmlspecialchars($usr['added_at'] ?? '') ?></td>
								<td><?= htmlspecialchars($usr['added_by']['name'] ?? '') ?></td>
								<td>
									<form method="POST" style="display: inline-block;">
										<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($currentUserAuthId ?? '') ?>">
										<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($portalDomain ?? '') ?>">
										<input type="hidden" name="action" value="remove_user">
										<input type="hidden" name="user_id" value="<?= htmlspecialchars($usr['id'] ?? '') ?>">
										<button type="submit" class="btn btn-danger" 
											onclick="return confirm('Вы уверены, что хотите удалить этого пользователя из списка доступа?')">
											Удалить
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				<div class="empty-state">Нет пользователей с доступом</div>
			<?php endif; ?>
			
			<form method="POST" class="add-form">
				<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($currentUserAuthId ?? '') ?>">
				<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($portalDomain ?? '') ?>">
				<input type="hidden" name="action" value="add_user">
				<input type="text" name="user_search" id="user-search" placeholder="Поиск пользователей (имя или email)" 
					onkeyup="filterUsers(this.value)">
				<select name="user_id" id="user-select" required>
					<option value="">Выберите пользователя</option>
					<?php foreach ($allUsers as $usr): ?>
						<option value="<?= htmlspecialchars($usr['id']) ?>" 
							data-name="<?= htmlspecialchars($usr['name']) ?>"
							data-email="<?= htmlspecialchars($usr['email'] ?? '') ?>">
							<?= htmlspecialchars($usr['name']) ?><?= $usr['email'] ? ' (' . htmlspecialchars($usr['email']) . ')' : '' ?>
						</option>
					<?php endforeach; ?>
				</select>
				<input type="hidden" name="user_name" id="user-name">
				<input type="hidden" name="user_email" id="user-email">
				<button type="submit" class="btn btn-primary" id="add-user-btn">Добавить пользователя</button>
			</form>
		</div>
		
		<div class="footer">
			<form method="POST" action="index.php" style="display: inline-block;">
				<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($currentUserAuthId ?? '') ?>">
				<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($portalDomain ?? '') ?>">
				<button type="submit" class="btn btn-primary">← Назад на главную</button>
			</form>
		</div>
	</div>
	
	<script>
		// Обработка выбора отдела
		document.getElementById('department-select')?.addEventListener('change', function() {
			const selectedOption = this.options[this.selectedIndex];
			if (selectedOption.value) {
				document.getElementById('department-name').value = selectedOption.getAttribute('data-name');
			}
		});
		
		// Фильтрация пользователей в выпадающем списке
		function filterUsers(query) {
			const select = document.getElementById('user-select');
			const options = select.querySelectorAll('option');
			const searchQuery = query.toLowerCase().trim();
			
			let visibleCount = 0;
			
			options.forEach(function(option) {
				if (option.value === '') {
					// Пропускаем первую опцию "Выберите пользователя"
					return;
				}
				
				const text = option.textContent.toLowerCase();
				const name = option.getAttribute('data-name')?.toLowerCase() || '';
				const email = option.getAttribute('data-email')?.toLowerCase() || '';
				
				if (searchQuery === '' || 
					text.includes(searchQuery) || 
					name.includes(searchQuery) || 
					email.includes(searchQuery)) {
					option.style.display = '';
					visibleCount++;
				} else {
					option.style.display = 'none';
				}
			});
			
			// Если ничего не найдено, показываем сообщение
			if (searchQuery !== '' && visibleCount === 0) {
				// Можно добавить временную опцию "Не найдено"
				// Но лучше просто скрыть все опции
			}
		}
		
		// Обработка выбора пользователя
		document.getElementById('user-select')?.addEventListener('change', function() {
			const selectedOption = this.options[this.selectedIndex];
			if (selectedOption.value) {
				const userName = selectedOption.getAttribute('data-name') || selectedOption.textContent.split(' (')[0];
				const userEmail = selectedOption.getAttribute('data-email') || '';
				
				document.getElementById('user-name').value = userName;
				document.getElementById('user-email').value = userEmail;
				
				// Логирование для отладки
				console.log('Selected user:', {
					id: selectedOption.value,
					name: userName,
					email: userEmail
				});
			} else {
				document.getElementById('user-name').value = '';
				document.getElementById('user-email').value = '';
			}
		});
		
	</script>
</body>
</html>

