<?php
/**
 * Отладочная версия index.php для диагностики
 */
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>\n";
echo "<html lang=\"ru\">\n";
echo "<head>\n";
echo "    <meta charset=\"UTF-8\">\n";
echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
echo "    <title>Debug - Bitrix24 REST Приложение</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }\n";
echo "        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo "        .success { color: green; }\n";
echo "        .error { color: red; }\n";
echo "        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<h1>🔍 Отладка APP-B24</h1>\n";

// 1. Проверка конфигурации
echo "<div class=\"section\">\n";
echo "<h2>1. Конфигурация</h2>\n";
try {
    require_once(__DIR__ . '/src/bootstrap.php');
    $config = $configService->getIndexPageConfig();
    $externalAccess = $config['external_access'] ?? false;
    echo "<p class=\"" . ($externalAccess ? 'success' : 'error') . "\">";
    echo "external_access: " . ($externalAccess ? 'true ✅' : 'false ❌');
    echo "</p>\n";
    echo "<pre>" . print_r($config, true) . "</pre>\n";
} catch (Exception $e) {
    echo "<p class=\"error\">Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
echo "</div>\n";

// 2. Проверка Vue.js файлов
echo "<div class=\"section\">\n";
echo "<h2>2. Vue.js файлы</h2>\n";
$vueAppPath = __DIR__ . '/public/dist/index.html';
if (file_exists($vueAppPath)) {
    echo "<p class=\"success\">index.html найден ✅</p>\n";
    $html = file_get_contents($vueAppPath);
    echo "<p>Размер файла: " . strlen($html) . " байт</p>\n";
    echo "<p>Первые 200 символов:</p>\n";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 200)) . "...</pre>\n";
} else {
    echo "<p class=\"error\">index.html НЕ найден ❌</p>\n";
    echo "<p>Путь: " . htmlspecialchars($vueAppPath) . "</p>\n";
}
echo "</div>\n";

// 3. Проверка статических файлов
echo "<div class=\"section\">\n";
echo "<h2>3. Статические файлы</h2>\n";
$jsFile = __DIR__ . '/public/dist/assets/main-DYnjAQE_.js';
$cssFile = __DIR__ . '/public/dist/assets/main-xoOtiISG.css';

if (file_exists($jsFile)) {
    echo "<p class=\"success\">JS файл найден ✅ (" . filesize($jsFile) . " байт)</p>\n";
} else {
    echo "<p class=\"error\">JS файл НЕ найден ❌</p>\n";
}

if (file_exists($cssFile)) {
    echo "<p class=\"success\">CSS файл найден ✅ (" . filesize($cssFile) . " байт)</p>\n";
} else {
    echo "<p class=\"error\">CSS файл НЕ найден ❌</p>\n";
}
echo "</div>\n";

// 4. Проверка заголовков
echo "<div class=\"section\">\n";
echo "<h2>4. Заголовки ответа</h2>\n";
echo "<pre>";
echo "Content-Type: " . (headers_sent() ? 'уже отправлен' : 'не отправлен') . "\n";
echo "HTTP/1.1 200 OK\n";
echo "</pre>\n";
echo "</div>\n";

// 5. Тест загрузки через VueAppService
echo "<div class=\"section\">\n";
echo "<h2>5. Тест VueAppService</h2>\n";
try {
    $vueAppService = new App\Services\VueAppService($logger);
    if ($vueAppService->checkVueAppExists()) {
        echo "<p class=\"success\">VueAppService: файлы найдены ✅</p>\n";
    } else {
        echo "<p class=\"error\">VueAppService: файлы НЕ найдены ❌</p>\n";
    }
} catch (Exception $e) {
    echo "<p class=\"error\">Ошибка VueAppService: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
echo "</div>\n";

// 6. Ссылки
echo "<div class=\"section\">\n";
echo "<h2>6. Ссылки для проверки</h2>\n";
echo "<ul>\n";
echo "<li><a href=\"/APP-B24/index.php\" target=\"_blank\">index.php (основной)</a></li>\n";
echo "<li><a href=\"/APP-B24/debug-index.php\" target=\"_blank\">debug-index.php (эта страница)</a></li>\n";
echo "<li><a href=\"/APP-B24/public/dist/assets/main-DYnjAQE_.js\" target=\"_blank\">JS файл</a></li>\n";
echo "<li><a href=\"/APP-B24/public/dist/assets/main-xoOtiISG.css\" target=\"_blank\">CSS файл</a></li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</body>\n";
echo "</html>\n";



