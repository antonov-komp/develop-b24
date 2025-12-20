<?php
/**
 * Шаблон страницы анализа токена
 * 
 * Переменные:
 * - $analysisResult - результат анализа токена
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
		background: #f5f5f5;
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
	
	.container {
		max-width: 1200px;
		margin: 0 auto;
		background: white;
		border-radius: 8px;
		padding: 30px;
		box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
	
	h1 {
		margin-bottom: 20px;
		color: #333;
	}
	
	.json-container {
		position: relative;
		opacity: 0;
		animation: fadeInUp 0.5s ease-out 0.6s forwards;
	}
	
	textarea {
		width: 100%;
		min-height: 500px;
		font-family: 'Courier New', monospace;
		font-size: 14px;
		padding: 15px;
		border: 1px solid #ddd;
		border-radius: 4px;
		resize: vertical;
		background: #f8f9fa;
		transition: border-color 0.3s ease, box-shadow 0.3s ease;
	}
	
	textarea:focus {
		outline: none;
		border-color: #667eea;
		box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
	}
	
	.copy-button {
		position: absolute;
		top: 10px;
		right: 10px;
		background: #667eea;
		color: white;
		border: none;
		padding: 10px 20px;
		border-radius: 4px;
		cursor: pointer;
		font-size: 14px;
		font-weight: 600;
		transition: all 0.3s ease;
		opacity: 0;
		animation: fadeIn 0.5s ease-out 0.8s forwards;
	}
	
	.copy-button:hover {
		background: #5568d3;
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
	}
	
	.copy-button:active {
		background: #4457c2;
		transform: translateY(0);
	}
	
	.success-message {
		display: none;
		position: fixed;
		top: 20px;
		right: 20px;
		background: #28a745;
		color: white;
		padding: 15px 20px;
		border-radius: 4px;
		box-shadow: 0 2px 10px rgba(0,0,0,0.2);
		z-index: 1000;
		animation: slideIn 0.3s ease-out;
	}
	
	@keyframes slideIn {
		from {
			transform: translateX(100%);
			opacity: 0;
		}
		to {
			transform: translateX(0);
			opacity: 1;
		}
	}
	
	.info-box {
		background: #e7f3ff;
		border-left: 4px solid #667eea;
		padding: 15px;
		border-radius: 8px;
		margin-bottom: 20px;
		opacity: 0;
		animation: fadeInUp 0.5s ease-out 0.4s forwards;
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
	
	.info-box p {
		margin: 5px 0;
		font-size: 14px;
		color: #333;
	}
	
	.info-box strong {
		color: #667eea;
	}
	
	@keyframes fadeInLeft {
		from {
			opacity: 0;
			transform: translateX(-20px);
		}
		to {
			opacity: 1;
			transform: translateX(0);
		}
	}
	
	button[type="submit"]:hover {
		background: #667eea !important;
		color: white !important;
		transform: translateX(-3px);
		box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
	}
</style>
<?php
$styles = ob_get_clean();
?>

<div class="container">
	<div style="margin-bottom: 20px; opacity: 0; animation: fadeInLeft 0.5s ease-out 0.3s forwards;">
		<form method="POST" action="index.php" style="display: inline-block;">
			<?php if (!empty($_REQUEST['AUTH_ID'])): ?>
				<input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($_REQUEST['AUTH_ID']) ?>">
			<?php endif; ?>
			<?php if (!empty($_REQUEST['DOMAIN'])): ?>
				<input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>">
			<?php endif; ?>
			<button type="submit" 
					style="background: transparent; color: #667eea; border: 2px solid #667eea; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">
				← Назад к главной
			</button>
		</form>
	</div>
	
	<h1>Анализ токена Bitrix24</h1>
	
	<div class="info-box">
		<p><strong>Время анализа:</strong> <?= htmlspecialchars($analysisResult['analysis_timestamp']) ?></p>
		<p><strong>Домен портала:</strong> <?= htmlspecialchars($analysisResult['portal_info']['domain']) ?> (источник: <?= htmlspecialchars($analysisResult['portal_info']['domain_source']) ?>)</p>
		<?php if (isset($analysisResult['analysis_execution_time_ms'])): ?>
			<p><strong>Время выполнения:</strong> <?= htmlspecialchars($analysisResult['analysis_execution_time_ms']) ?> мс</p>
		<?php endif; ?>
		<?php if (!empty($analysisResult['errors'])): ?>
			<p><strong>Ошибок:</strong> <?= count($analysisResult['errors']) ?></p>
		<?php endif; ?>
	</div>
	
	<div class="json-container">
		<textarea id="json-output" readonly><?= htmlspecialchars(json_encode($analysisResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
		<button class="copy-button" onclick="copyJson()">Копировать JSON</button>
	</div>
</div>

<div class="success-message" id="success-message">
	JSON скопирован в буфер обмена!
</div>

<script>
	function copyJson() {
		const textarea = document.getElementById('json-output');
		textarea.select();
		textarea.setSelectionRange(0, 99999); // Для мобильных устройств
		
		try {
			// Пробуем использовать современный Clipboard API
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(textarea.value).then(function() {
					showSuccessMessage();
				}).catch(function(err) {
					// Fallback на execCommand
					fallbackCopy();
				});
			} else {
				// Fallback для старых браузеров
				fallbackCopy();
			}
		} catch (err) {
			fallbackCopy();
		}
	}
	
	function fallbackCopy() {
		const textarea = document.getElementById('json-output');
		textarea.select();
		textarea.setSelectionRange(0, 99999);
		
		try {
			document.execCommand('copy');
			showSuccessMessage();
		} catch (err) {
			alert('Не удалось скопировать. Пожалуйста, скопируйте вручную (Ctrl+C или Cmd+C).');
		}
	}
	
	function showSuccessMessage() {
		const message = document.getElementById('success-message');
		message.style.display = 'block';
		setTimeout(function() {
			message.style.display = 'none';
		}, 2000);
	}
</script>

