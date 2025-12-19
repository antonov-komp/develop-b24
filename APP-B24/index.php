<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * Защищена от прямого доступа - работает только внутри Bitrix24 при активной авторизации
 * Документация: https://context7.com/bitrix24/rest/
 */

require_once(__DIR__ . '/auth-check.php');

// Проверка авторизации Bitrix24
if (!checkBitrix24Auth()) {
	redirectToFailure();
}

require_once(__DIR__ . '/crest.php');

$result = CRest::call(
	'profile',
	[
	]
);

echo '<pre>';
print_r($result);
echo '</pre>';