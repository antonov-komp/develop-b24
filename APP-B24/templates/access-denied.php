<?php
/**
 * Шаблон страницы ошибки доступа
 * 
 * Переменные:
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
<?php
$styles = ob_get_clean();
?>

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

