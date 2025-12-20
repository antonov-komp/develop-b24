<?php
/**
 * Шаблон страницы управления правами доступа
 * 
 * Переменные:
 * - $message - сообщение для пользователя
 * - $messageType - тип сообщения ('success' или 'error')
 * - $accessConfig - конфигурация прав доступа
 * - $allDepartments - список всех отделов
 * - $allUsers - список всех пользователей
 * - $currentUserAuthId - токен авторизации
 * - $portalDomain - домен портала
 */
?>

<?php
// Стили для страницы
ob_start();
?>
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
<?php
$styles = ob_get_clean();
?>

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

