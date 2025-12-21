<?php
/**
 * Страница ошибки при деактивации интерфейса через конфиг
 * 
 * Отображается когда конфигурационный файл config.json отключает доступ к интерфейсу
 */

// Получаем сообщение из параметров или используем значение по умолчанию
// PHP автоматически декодирует URL-параметры, но на всякий случай используем urldecode()
$message = isset($_GET['message']) ? urldecode($_GET['message']) : (isset($_POST['message']) ? urldecode($_POST['message']) : 'Интерфейс приложения временно недоступен. Пожалуйста, попробуйте позже.');
$lastUpdated = isset($_GET['last_updated']) ? urldecode($_GET['last_updated']) : (isset($_POST['last_updated']) ? urldecode($_POST['last_updated']) : null);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Интерфейс недоступен</title>
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
        }
        
        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .last-updated {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }
        
        .refresh-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .refresh-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Интерфейс недоступен</h1>
        <p><?= htmlspecialchars($message) ?></p>
        <?php if ($lastUpdated): ?>
            <div class="last-updated">
                Последнее обновление: <?= htmlspecialchars($lastUpdated) ?>
            </div>
        <?php endif; ?>
        <button class="refresh-button" onclick="window.location.reload()">
            Обновить страницу
        </button>
    </div>
</body>
</html>

