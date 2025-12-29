<?php
/**
 * Тестовая PHP страница для backend.antonov-mark.ru
 * 
 * Дата создания: 2025-12-19 (UTC+3, Брест)
 */

// Установка часового пояса
date_default_timezone_set('Europe/Minsk');

// Получение информации о сервере
$serverInfo = [
    'domain' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'protocol' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP',
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'server_time' => date('Y-m-d H:i:s T'),
    'timezone' => date_default_timezone_get(),
];

// Проверка PHP-FPM
$phpFpmStatus = function_exists('phpinfo') ? 'Доступен' : 'Недоступен';

// Получение информации о PHP
$phpInfo = [
    'version' => PHP_VERSION,
    'sapi' => php_sapi_name(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
];

// Получение IP адреса клиента
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Info - backend.antonov-mark.ru</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #10b981;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
            border-left: 4px solid #10b981;
        }
        .section h2 {
            color: #10b981;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .info-item strong {
            color: #10b981;
            display: block;
            margin-bottom: 5px;
        }
        .info-item span {
            color: #374151;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #10b981;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: #059669;
        }
        .php-version {
            font-size: 1.2em;
            color: #10b981;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐘 PHP Information</h1>
        <p class="subtitle">Информация о PHP и сервере</p>
        
        <div class="status-badge">✅ PHP работает!</div>
        
        <div class="section">
            <h2>Информация о сервере</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Домен:</strong>
                    <span><?= htmlspecialchars($serverInfo['domain']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Протокол:</strong>
                    <span><?= htmlspecialchars($serverInfo['protocol']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Время сервера:</strong>
                    <span><?= htmlspecialchars($serverInfo['server_time']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Часовой пояс:</strong>
                    <span><?= htmlspecialchars($serverInfo['timezone']) ?></span>
                </div>
                <div class="info-item">
                    <strong>IP клиента:</strong>
                    <span><?= htmlspecialchars($clientIp) ?></span>
                </div>
                <div class="info-item">
                    <strong>Document Root:</strong>
                    <span><?= htmlspecialchars($serverInfo['document_root']) ?></span>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Информация о PHP</h2>
            <div class="php-version">Версия PHP: <?= htmlspecialchars($phpInfo['version']) ?></div>
            <div class="info-grid">
                <div class="info-item">
                    <strong>SAPI:</strong>
                    <span><?= htmlspecialchars($phpInfo['sapi']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Memory Limit:</strong>
                    <span><?= htmlspecialchars($phpInfo['memory_limit']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Max Execution Time:</strong>
                    <span><?= htmlspecialchars($phpInfo['max_execution_time']) ?> сек</span>
                </div>
                <div class="info-item">
                    <strong>Upload Max Filesize:</strong>
                    <span><?= htmlspecialchars($phpInfo['upload_max_filesize']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Post Max Size:</strong>
                    <span><?= htmlspecialchars($phpInfo['post_max_size']) ?></span>
                </div>
                <div class="info-item">
                    <strong>PHP-FPM:</strong>
                    <span><?= htmlspecialchars($phpFpmStatus) ?></span>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Заголовки запроса</h2>
            <div class="info-grid">
                <?php foreach ($_SERVER as $key => $value): ?>
                    <?php if (strpos($key, 'HTTP_') === 0 || in_array($key, ['REQUEST_METHOD', 'REQUEST_URI', 'QUERY_STRING'])): ?>
                        <div class="info-item">
                            <strong><?= htmlspecialchars($key) ?>:</strong>
                            <span><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <a href="/" class="back-link">← Вернуться на главную</a>
    </div>
</body>
</html>



