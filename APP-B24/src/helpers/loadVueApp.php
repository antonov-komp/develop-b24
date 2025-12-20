<?php
/**
 * Вспомогательная функция для загрузки Vue.js приложения
 * 
 * Используется в index.php, access-control.php, token-analysis.php
 * для единообразной загрузки Vue.js фронтенда
 */

function loadVueApp(?string $initialRoute = null): void
{
    // Определение окружения
    $appEnv = getenv('APP_ENV') ?: 'production';
    
    // Путь к собранным файлам (от корня проекта)
    // __DIR__ здесь = APP-B24/src/helpers/
    // Нужно подняться на 2 уровня вверх: APP-B24/
    // Используем реальный путь от файла loadVueApp.php
    $indexHtml = __DIR__ . '/../../public/dist/index.html';
    
    // Нормализуем путь (убираем ../ и ./)
    $indexHtml = realpath($indexHtml);
    
    // Если realpath вернул false, значит файл не существует
    if ($indexHtml === false) {
        $indexHtml = __DIR__ . '/../../public/dist/index.html';
    }
    
    // Проверка существования собранных файлов
    if (!file_exists($indexHtml) || $indexHtml === false) {
        if ($appEnv === 'development') {
            http_response_code(503);
            die('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Vue.js приложение не собрано</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 40px; text-align: center; }
                    h1 { color: #e74c3c; }
                    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
                </style>
            </head>
            <body>
                <h1>Vue.js приложение не собрано</h1>
                <p>Для запуска приложения необходимо собрать фронтенд:</p>
                <p><code>cd frontend && npm install && npm run build</code></p>
                <p>Или запустить dev-сервер:</p>
                <p><code>cd frontend && npm run dev</code></p>
            </body>
            </html>
            ');
        } else {
            http_response_code(503);
            die('Service temporarily unavailable');
        }
    }
    
    // Чтение index.html
    $html = file_get_contents($indexHtml);
    
    // Пути в index.html уже правильные (содержат /APP-B24/public/dist/)
    // Не нужно их заменять, только добавляем/обновляем base tag
    
    // Добавляем base tag, если его нет, или обновляем существующий
    if (strpos($html, '<base') === false) {
        // Вставляем base tag после <head>
        $html = str_replace('<head>', '<head><base href="/APP-B24/">', $html);
    } else {
        // Заменяем существующий base tag
        $html = preg_replace('/<base[^>]*>/', '<base href="/APP-B24/">', $html);
    }
    
    // Добавляем отладочный скрипт для проверки загрузки (только в development)
    if ($appEnv === 'development') {
        $debugScript = '
        <script>
            console.log("Vue.js app loading...", {
                base: document.querySelector("base")?.href,
                scripts: Array.from(document.querySelectorAll("script")).map(s => s.src),
                styles: Array.from(document.querySelectorAll("link[rel=stylesheet]")).map(l => l.href),
                appElement: document.querySelector("#app")
            });
        </script>
        ';
        $html = str_replace('</head>', $debugScript . '</head>', $html);
    }
    
    // Если указан начальный маршрут, добавляем скрипт для навигации
    if ($initialRoute && $initialRoute !== '/') {
        // Сохраняем query параметры из текущего URL
        $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        
        $navigationScript = '
        <script>
            (function() {
                // Простая навигация через изменение URL
                // Vue Router автоматически обработает изменение URL при загрузке
                const targetRoute = "' . htmlspecialchars($initialRoute, ENT_QUOTES) . '";
                const basePath = "/APP-B24";
                const newPath = basePath + targetRoute;
                const queryString = "' . htmlspecialchars($queryString, ENT_QUOTES) . '";
                
                // Изменяем URL без перезагрузки страницы
                if (window.history && window.history.pushState) {
                    window.history.replaceState({}, "", newPath + queryString);
                }
                
                // После загрузки Vue.js роутер автоматически обработает новый URL
                // Если роутер еще не готов, попробуем через небольшую задержку
                function tryRouterNavigation() {
                    const appElement = document.querySelector("#app");
                    if (appElement && appElement.__vue_app__) {
                        const app = appElement.__vue_app__;
                        if (app.config && app.config.globalProperties && app.config.globalProperties.$router) {
                            const router = app.config.globalProperties.$router;
                            router.push(targetRoute).catch(function(err) {
                                // Игнорируем ошибки навигации (например, если уже на этом маршруте)
                                if (err.name !== "NavigationDuplicated") {
                                    console.warn("Router navigation:", err);
                                }
                            });
                            return true;
                        }
                    }
                    return false;
                }
                
                // Пытаемся выполнить навигацию через роутер
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", function() {
                        setTimeout(function() {
                            if (!tryRouterNavigation()) {
                                // Если роутер еще не готов, повторяем попытку
                                setTimeout(tryRouterNavigation, 500);
                            }
                        }, 100);
                    });
                } else {
                    setTimeout(function() {
                        if (!tryRouterNavigation()) {
                            setTimeout(tryRouterNavigation, 500);
                        }
                    }, 100);
                }
            })();
        </script>
        ';
        
        // Вставляем скрипт перед закрывающим тегом </body>
        $html = str_replace('</body>', $navigationScript . '</body>', $html);
    }
    
    // Вывод HTML
    echo $html;
    exit;
}

