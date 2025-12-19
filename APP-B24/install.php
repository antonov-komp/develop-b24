<?php
/**
 * Страница установки приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только при установке из Bitrix24
 * Документация: https://context7.com/bitrix24/rest/
 */

require_once(__DIR__ . '/auth-check.php');

// Проверка авторизации Bitrix24
// Для install.php разрешаем доступ только если есть параметры установки от Bitrix24
if (!checkBitrix24Auth()) {
	redirectToFailure();
}

require_once(__DIR__ . '/crest.php');

$result = CRest::installApp();
if($result['rest_only'] === false):?>
<head>
	<script src="//api.bitrix24.com/api/v1/"></script>
	<?php if($result['install'] == true): ?>
	<script>
		BX24.init(function(){
			BX24.installFinish();
		});
	</script>
	<?php endif; ?>
</head>
<body>
	<?php if($result['install'] == true): ?>
		installation has been finished
	<?php else: ?>
		installation error
	<?php endif; ?>
</body>
<?php endif; ?>