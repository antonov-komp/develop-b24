<?php
/**
 * Шаблон главной страницы
 * 
 * Переменные:
 * - $user - данные пользователя
 * - $userFullName - полное имя пользователя
 * - $isAdmin - статус администратора
 * - $adminStatus - текст статуса
 * - $portalDomain - домен портала
 * - $departmentId - ID отдела
 * - $departmentName - название отдела
 * - $userPhoto - фото пользователя
 * - $isCurrentUserToken - используется ли токен текущего пользователя
 * - $debugMode - режим отладки
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
<?php
$styles = ob_get_clean();
?>

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
		
		<?php if ($debugMode): ?>
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

