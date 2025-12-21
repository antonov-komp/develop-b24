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
    
    // НЕ добавляем base tag, так как пути уже абсолютные
    // Base tag может конфликтовать с абсолютными путями в iframe
    
    // Подключаем BX24 SDK для получения токена авторизации
    // Также передаем токен из PHP, если он есть в POST параметрах
    // Используем AUTH_ID из POST, если есть, иначе из GET, иначе APP_SID из GET
    $authId = $_POST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_GET['APP_SID'] ?? null;
    $refreshId = $_POST['REFRESH_ID'] ?? $_GET['REFRESH_ID'] ?? null;
    $authExpires = isset($_POST['AUTH_EXPIRES']) ? (int)$_POST['AUTH_EXPIRES'] : (isset($_GET['AUTH_EXPIRES']) ? (int)$_GET['AUTH_EXPIRES'] : null);
    $domain = $_POST['DOMAIN'] ?? $_GET['DOMAIN'] ?? null;
    
    $bx24Script = '
    <script src="//api.bitrix24.com/api/v1/"></script>
    <script>
        // Передаем токен из PHP в JavaScript, если он есть
        ' . ($authId && $domain ? '
        (function() {
            const authData = {
                auth_token: ' . json_encode($authId, JSON_UNESCAPED_UNICODE) . ',
                refresh_token: ' . ($refreshId ? json_encode($refreshId, JSON_UNESCAPED_UNICODE) : 'null') . ',
                expires: ' . ($authExpires ?: 'null') . ',
                domain: ' . json_encode($domain, JSON_UNESCAPED_UNICODE) . '
            };
            
            // Сохраняем токен в sessionStorage
            sessionStorage.setItem("bitrix24_auth", JSON.stringify(authData));
            console.log("Auth token from PHP saved to sessionStorage", {
                auth_token_length: authData.auth_token.length,
                domain: authData.domain,
                has_refresh_token: !!authData.refresh_token
            });
        })();
        ' : '') . '
        
        // Инициализация BX24 SDK (fallback, если токен не был передан из PHP)
        if (typeof BX24 !== "undefined" && typeof BX24.init === "function") {
            BX24.init(function() {
                console.log("BX24 SDK initialized");
                
                // Получаем токен авторизации только если его еще нет в sessionStorage
                if (!sessionStorage.getItem("bitrix24_auth")) {
                    BX24.getAuth(function(auth) {
                        if (auth && auth.auth_token) {
                            sessionStorage.setItem("bitrix24_auth", JSON.stringify(auth));
                            console.log("BX24 auth token saved to sessionStorage", {
                                auth_token_length: auth.auth_token.length,
                                domain: auth.domain
                            });
                        }
                    });
                }
            });
        }
    </script>
    ';
    
    // Добавляем отладочный скрипт для проверки загрузки (временно в production тоже)
    $debugScript = '
    <script>
        console.log("=== Vue.js App Debug ===");
        console.log("Location:", window.location.href);
        console.log("Base tag:", document.querySelector("base")?.href || "none");
        console.log("Scripts:", Array.from(document.querySelectorAll("script")).map(s => s.src));
        console.log("Styles:", Array.from(document.querySelectorAll("link[rel=stylesheet]")).map(l => l.href));
        console.log("App element:", document.querySelector("#app"));
        
        // Проверка загрузки скриптов
        window.addEventListener("error", function(e) {
            console.error("Script load error:", e.filename, e.message);
        });
        
        // Проверка, что Vue.js загрузился
        setTimeout(function() {
            const app = document.querySelector("#app");
            if (app && !app.__vue_app__) {
                console.error("Vue.js app not mounted after 2 seconds!");
            } else if (app && app.__vue_app__) {
                console.log("Vue.js app mounted successfully");
            }
        }, 2000);
    </script>
    ';
    $html = str_replace('</head>', $bx24Script . $debugScript . '</head>', $html);
    
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

