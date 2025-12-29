<?php
/**
 * Простая тестовая страница для проверки отображения
 */
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Простой тест</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            text-align: center;
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            font-size: 24px;
            margin: 20px 0;
        }
        .info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Страница работает!</h1>
        <div class="success">Если вы видите это сообщение, значит PHP и Nginx работают правильно.</div>
        
        <div class="info">
            <strong>Информация:</strong><br>
            Время сервера: <?= date('Y-m-d H:i:s') ?><br>
            PHP версия: <?= PHP_VERSION ?><br>
            Content-Type: text/html; charset=UTF-8
        </div>
        
        <div class="info">
            <strong>Следующие шаги:</strong><br>
            1. Если вы видите эту страницу - сервер работает ✅<br>
            2. Проверьте основное приложение: <a href="/APP-B24/index.php">index.php</a><br>
            3. Откройте консоль браузера (F12) и проверьте ошибки<br>
            4. Проверьте загрузку JS/CSS файлов в Network (F12 → Network)
        </div>
        
        <a href="/APP-B24/index.php">Перейти к приложению</a>
    </div>
</body>
</html>



