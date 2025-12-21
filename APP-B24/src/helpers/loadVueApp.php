<?php
/**
 * Обёртка для обратной совместимости
 * 
 * Использует VueAppService для загрузки Vue.js приложения
 * Сохраняет старую сигнатуру функции для обратной совместимости
 * 
 * @param string|null $initialRoute Начальный маршрут для роутера Vue.js
 * @param array|null $appData Данные для передачи в Vue.js приложение
 * @return void
 * @throws \Exception Если файлы Vue.js не найдены
 */
function loadVueApp(?string $initialRoute = null, ?array $appData = null): void
{
    global $vueAppService;
    
    // Если сервис не инициализирован, создаём его
    if (!isset($vueAppService)) {
        // Проверяем, есть ли LoggerService в глобальной области
        global $logger;
        if (!isset($logger)) {
            require_once(__DIR__ . '/../Services/LoggerService.php');
            $logger = new App\Services\LoggerService();
        }
        require_once(__DIR__ . '/../Services/VueAppService.php');
        $vueAppService = new App\Services\VueAppService($logger);
    }
    
    $route = $initialRoute ?? '/';
    $vueAppService->load($route, $appData);
    
    // Старый код ниже больше не используется, но оставлен для справки
    return;
    
    // ========== СТАРЫЙ КОД (не используется) ==========
    /*
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
        // Логирование ошибки
        error_log(sprintf(
            '[loadVueApp] Vue.js app not found: %s (script: %s)',
            $indexHtml,
            basename($_SERVER['PHP_SELF'] ?? 'unknown')
        ));
        
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
    
    // Используем переданные данные (приоритет) или данные из глобальной переменной (для обратной совместимости)
    // TODO: Удалить поддержку $GLOBALS после обновления всех вызовов
    $vueAppData = $appData ?? ($GLOBALS['vue_app_data'] ?? null);
    
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
        
        // Передача данных из PHP в Vue.js
        ' . buildVueAppDataScript($vueAppData) . '
        
        // Инициализация BX24 SDK для получения правильного токена (если токен не был передан из PHP)
        if (typeof BX24 !== "undefined" && typeof BX24.init === "function") {
            BX24.init(function() {
                // Получаем токен авторизации только если его еще нет в sessionStorage
                if (!sessionStorage.getItem("bitrix24_auth")) {
                    BX24.getAuth(function(auth) {
                        if (auth && auth.auth_token) {
                            sessionStorage.setItem("bitrix24_auth", JSON.stringify(auth));
                        }
                    });
                }
            });
        }
    </script>
    ';
    
    $html = str_replace('</head>', $bx24Script . '</head>', $html);
    
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
    
    // Логирование успешной загрузки
    error_log(sprintf(
        '[loadVueApp] Vue.js app loaded successfully (route: %s, has_data: %s)',
        $initialRoute ?? '/',
        $vueAppData ? 'yes' : 'no'
    ));
    
    // Вывод HTML
    echo $html;
    exit;
    */
}

/**
 * Построение JavaScript-скрипта для передачи данных в Vue.js
 * 
 * @param array|null $appData Данные для передачи
 * @return string JavaScript-код (пустая строка, если данных нет)
 */
function buildVueAppDataScript(?array $appData): string
{
    if ($appData === null || empty($appData)) {
        return '';
    }
    
    // Валидация данных перед кодированием
    if (!is_array($appData)) {
        error_log('[buildVueAppDataScript] Invalid appData: expected array, got ' . gettype($appData));
        return '';
    }
    
    $jsonData = json_encode($appData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if ($jsonData === false) {
        error_log('[buildVueAppDataScript] Failed to encode appData: ' . json_last_error_msg());
        return '';
    }
    
    return '
        (function() {
            const appData = ' . $jsonData . ';
            
            // Сохраняем данные в sessionStorage для использования в Vue.js
            sessionStorage.setItem("app_data", JSON.stringify(appData));
            
            // Также сохраняем в window для прямого доступа
            window.__APP_DATA__ = appData;
            
            console.log("App data from PHP saved", {
                is_authenticated: appData.authInfo?.is_authenticated,
                is_admin: appData.authInfo?.is_admin,
                user_name: appData.authInfo?.user?.full_name,
                external_access: appData.externalAccessEnabled
            });
        })();
    ';
}

