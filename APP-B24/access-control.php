<?php
/**
 * Страница управления правами доступа в приложении Bitrix24
 * 
 * Позволяет администраторам настраивать, кто имеет доступ к приложению
 * Документация: https://context7.com/bitrix24/rest/
 */

require_once(__DIR__ . '/auth-check.php');

// Проверка авторизации Bitrix24
if (!checkBitrix24Auth()) {
	redirectToFailure();
}

require_once(__DIR__ . '/access-control-functions.php');
require_once(__DIR__ . '/crest.php');

// Получение данных текущего пользователя
$currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;
$portalDomain = $_REQUEST['DOMAIN'] ?? null;

// Получаем домен из settings.json, если не передан в запросе
if (!$portalDomain) {
	$settingsFile = __DIR__ . '/settings.json';
	if (file_exists($settingsFile)) {
		$settingsContent = file_get_contents($settingsFile);
		$settings = json_decode($settingsContent, true);
		if (isset($settings['domain']) && !empty($settings['domain']) && $settings['domain'] !== 'oauth.bitrix.info') {
			$portalDomain = $settings['domain'];
		} elseif (isset($settings['client_endpoint']) && !empty($settings['client_endpoint'])) {
			if (preg_match('#https?://([^/]+)#', $settings['client_endpoint'], $matches)) {
				$portalDomain = $matches[1];
			}
		}
	}
}

// Обработка AJAX-запроса поиска пользователей (до проверки администратора)
if (isset($_GET['action']) && $_GET['action'] === 'search_users' && isset($_GET['query'])) {
	// Минимальная проверка авторизации для AJAX
	if (!checkBitrix24Auth()) {
		header('Content-Type: application/json');
		echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	$searchQuery = $_GET['query'] ?? '';
	$foundUsers = getAllUsers($currentUserAuthId, $portalDomain, $searchQuery);
	header('Content-Type: application/json');
	echo json_encode(['users' => $foundUsers], JSON_UNESCAPED_UNICODE);
	exit;
}

// Получение данных пользователя для проверки администратора
// Используем функцию из access-control-functions.php
$userResult = getCurrentUserDataForAccess($currentUserAuthId, $portalDomain);
$user = null;
$isAdmin = false;

if (!isset($userResult['error']) && isset($userResult['result'])) {
	$user = $userResult['result'];
	$isAdmin = checkIsAdmin($user, $currentUserAuthId, $portalDomain);
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
			if (toggleAccessControl($enabled, $performedBy)) {
				logAccessControlOperation('toggle_enabled', ['enabled' => $enabled], $performedBy, true);
				$message = $enabled ? 'Проверка прав доступа включена' : 'Проверка прав доступа выключена';
				$messageType = 'success';
			} else {
				logAccessControlOperation('toggle_enabled', ['enabled' => $enabled], $performedBy, false);
				$message = 'Ошибка при изменении настройки';
				$messageType = 'error';
			}
			break;
			
		case 'add_department':
			$departmentId = (int)($_POST['department_id'] ?? 0);
			$departmentName = $_POST['department_name'] ?? '';
			
			if ($departmentId > 0 && !empty($departmentName)) {
				if (addDepartmentToAccess($departmentId, $departmentName, $performedBy)) {
					logAccessControlOperation('add_department', ['id' => $departmentId, 'name' => $departmentName], $performedBy, true);
					$message = 'Отдел добавлен в список доступа';
					$messageType = 'success';
				} else {
					logAccessControlOperation('add_department', ['id' => $departmentId, 'name' => $departmentName], $performedBy, false);
					$message = 'Ошибка при добавлении отдела (возможно, отдел уже есть в списке)';
					$messageType = 'error';
				}
			} else {
				$message = 'Не указан отдел';
				$messageType = 'error';
			}
			break;
			
		case 'remove_department':
			$departmentId = (int)($_POST['department_id'] ?? 0);
			
			if ($departmentId > 0) {
				if (removeDepartmentFromAccess($departmentId)) {
					logAccessControlOperation('remove_department', ['id' => $departmentId], $performedBy, true);
					$message = 'Отдел удалён из списка доступа';
					$messageType = 'success';
				} else {
					logAccessControlOperation('remove_department', ['id' => $departmentId], $performedBy, false);
					$message = 'Ошибка при удалении отдела';
					$messageType = 'error';
				}
			}
			break;
			
		case 'add_user':
			$userId = (int)($_POST['user_id'] ?? 0);
			$userName = $_POST['user_name'] ?? '';
			$userEmail = $_POST['user_email'] ?? null;
			
			if ($userId > 0 && !empty($userName)) {
				if (addUserToAccess($userId, $userName, $userEmail, $performedBy)) {
					logAccessControlOperation('add_user', ['id' => $userId, 'name' => $userName, 'email' => $userEmail], $performedBy, true);
					$message = 'Пользователь добавлен в список доступа';
					$messageType = 'success';
				} else {
					logAccessControlOperation('add_user', ['id' => $userId, 'name' => $userName, 'email' => $userEmail], $performedBy, false);
					$message = 'Ошибка при добавлении пользователя (возможно, пользователь уже есть в списке)';
					$messageType = 'error';
				}
			} else {
				$message = 'Не указан пользователь';
				$messageType = 'error';
			}
			break;
			
		case 'remove_user':
			$userId = (int)($_POST['user_id'] ?? 0);
			
			if ($userId > 0) {
				if (removeUserFromAccess($userId)) {
					logAccessControlOperation('remove_user', ['id' => $userId], $performedBy, true);
					$message = 'Пользователь удалён из списка доступа';
					$messageType = 'success';
				} else {
					logAccessControlOperation('remove_user', ['id' => $userId], $performedBy, false);
					$message = 'Ошибка при удалении пользователя';
					$messageType = 'error';
				}
			}
			break;
	}
}

// Получение текущей конфигурации
$accessConfig = getAccessConfig();

// Получение списка всех отделов для выпадающего списка
$allDepartments = [];
if ($currentUserAuthId && $portalDomain) {
	$allDepartments = getAllDepartments($currentUserAuthId, $portalDomain);
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
					onkeyup="searchUsers(this.value)">
				<select name="user_id" id="user-select" required style="display: none;">
					<option value="">Выберите пользователя</option>
				</select>
				<input type="hidden" name="user_name" id="user-name">
				<input type="hidden" name="user_email" id="user-email">
				<button type="submit" class="btn btn-primary" id="add-user-btn" style="display: none;">Добавить пользователя</button>
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
		
		// Поиск пользователей
		let searchTimeout;
		function searchUsers(query) {
			clearTimeout(searchTimeout);
			
			if (query.length < 2) {
				document.getElementById('user-select').style.display = 'none';
				document.getElementById('add-user-btn').style.display = 'none';
				return;
			}
			
			searchTimeout = setTimeout(function() {
				// Здесь можно добавить AJAX-запрос для поиска пользователей
				// Пока используем простой вариант - загружаем всех пользователей
				loadAllUsers(query);
			}, 500);
		}
		
		function loadAllUsers(searchQuery) {
			// В реальном приложении здесь должен быть AJAX-запрос
			// Для упрощения используем все доступные пользователи
			const select = document.getElementById('user-select');
			select.innerHTML = '<option value="">Загрузка...</option>';
			select.style.display = 'block';
			
			// Загружаем пользователей через форму (простой вариант)
			// В реальном приложении лучше использовать AJAX
			fetch('access-control.php?action=search_users&query=' + encodeURIComponent(searchQuery) + 
				'&AUTH_ID=<?= urlencode($currentUserAuthId ?? '') ?>&DOMAIN=<?= urlencode($portalDomain ?? '') ?>')
				.then(response => response.json())
				.then(data => {
					select.innerHTML = '<option value="">Выберите пользователя</option>';
					if (data.users && data.users.length > 0) {
						data.users.forEach(function(user) {
							const option = document.createElement('option');
							option.value = user.id;
							option.textContent = user.name + (user.email ? ' (' + user.email + ')' : '');
							option.setAttribute('data-name', user.name);
							option.setAttribute('data-email', user.email || '');
							select.appendChild(option);
						});
						document.getElementById('add-user-btn').style.display = 'block';
					} else {
						select.innerHTML = '<option value="">Пользователи не найдены</option>';
					}
				})
				.catch(error => {
					console.error('Ошибка поиска пользователей:', error);
					select.innerHTML = '<option value="">Ошибка загрузки</option>';
				});
		}
		
		// Обработка выбора пользователя
		document.getElementById('user-select')?.addEventListener('change', function() {
			const selectedOption = this.options[this.selectedIndex];
			if (selectedOption.value) {
				document.getElementById('user-name').value = selectedOption.getAttribute('data-name');
				document.getElementById('user-email').value = selectedOption.getAttribute('data-email');
			}
		});
		
	</script>
</body>
</html>

