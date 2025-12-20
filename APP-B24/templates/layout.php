<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $title ?? 'Bitrix24 Приложение' ?></title>
	<?php if (isset($styles) && !empty($styles)): ?>
		<style>
			<?= $styles ?>
		</style>
	<?php endif; ?>
</head>
<body>
	<?= $content ?? '' ?>
</body>
</html>

