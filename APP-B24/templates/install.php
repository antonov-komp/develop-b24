<?php
/**
 * Шаблон страницы установки приложения Bitrix24
 * 
 * Переменные:
 * - $result - результат установки от CRest::installApp()
 *   - $result['install'] - успешность установки (bool)
 *   - $result['rest_only'] - только REST API (bool)
 * - $error - сообщение об ошибке (если есть)
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка приложения Bitrix24</title>
    <script src="//api.bitrix24.com/api/v1/"></script>
    <?php if (isset($result['install']) && $result['install'] === true): ?>
    <script>
        BX24.init(function(){
            BX24.installFinish();
        });
    </script>
    <?php endif; ?>
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
        
        .install-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .install-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .install-success {
            color: #28a745;
        }
        
        .install-error {
            color: #dc3545;
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 16px;
            color: #333;
        }
        
        .message {
            font-size: 16px;
            color: #666;
            margin-top: 16px;
        }
        
        .error-message {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 12px;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <?php if (isset($result['install']) && $result['install'] === true): ?>
            <div class="install-icon install-success">✓</div>
            <h1>Установка завершена</h1>
            <p class="message">Приложение успешно установлено в Bitrix24</p>
        <?php else: ?>
            <div class="install-icon install-error">✗</div>
            <h1>Ошибка установки</h1>
            <?php if (isset($error) && !empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php else: ?>
                <p class="message">Произошла ошибка при установке приложения</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>

