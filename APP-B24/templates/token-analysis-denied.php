<?php
/**
 * Шаблон страницы ошибки доступа для анализа токена
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
		border: none;
		cursor: pointer;
	}
	
	.back-button:hover {
		transform: translateY(-3px);
		box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
	}
</style>
<?php
$styles = ob_get_clean();
?>

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
		$authIdForForm = $_POST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_REQUEST['AUTH_ID'] ?? $currentUserAuthId ?? null;
		$domainForForm = $_POST['DOMAIN'] ?? $_GET['DOMAIN'] ?? $_REQUEST['DOMAIN'] ?? $portalDomain ?? null;
		?>
		<?php if (!empty($authIdForForm)): ?>
			<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($authIdForForm) ?>">
		<?php endif; ?>
		<?php if (!empty($domainForForm)): ?>
			<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($domainForForm) ?>">
		<?php endif; ?>
		<button type="submit" class="back-button">
			← Вернуться на главную
		</button>
	</form>
</div>

