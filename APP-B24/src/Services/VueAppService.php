<?php

namespace App\Services;

/**
 * Сервис для загрузки Vue.js приложения
 * 
 * Обеспечивает единый интерфейс для загрузки Vue.js приложения
 * с передачей данных авторизации и настройкой начального маршрута
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class VueAppService
{
    protected LoggerService $logger;
    protected string $vueAppPath;
    protected string $appEnv;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->appEnv = getenv('APP_ENV') ?: 'production';
        
        // Путь к собранным файлам Vue.js
        $this->vueAppPath = __DIR__ . '/../../public/dist/index.html';
    }
    
    /**
     * Загрузка Vue.js приложения
     * 
     * @param string $route Начальный маршрут для роутера Vue.js
     * @param array|null $appData Данные для передачи в Vue.js приложение
     * @return void
     * @throws \Exception Если файлы Vue.js не найдены или ошибка чтения
     */
    public function load(string $route = '/', ?array $appData = null): void
    {
        // Проверка существования файлов Vue.js
        if (!$this->checkVueAppExists()) {
            $this->renderErrorPage(
                'Vue.js приложение не собрано',
                'Для запуска приложения необходимо собрать фронтенд: cd frontend && npm install && npm run build'
            );
            return;
        }
        
        // Чтение index.html
        $html = file_get_contents($this->vueAppPath);
        if ($html === false) {
            $this->logger->logError('VueAppService: Failed to read index.html', [
                'path' => $this->vueAppPath
            ]);
            throw new \Exception('Failed to read Vue.js application file');
        }
        
        // Получение параметров авторизации
        // Сначала проверяем appData (может содержать токен из settings.json при external_access)
        $authId = null;
        $domain = null;
        $externalAccessEnabled = false;
        if ($appData && isset($appData['authInfo'])) {
            $authInfo = $appData['authInfo'];
            if (!empty($authInfo['auth_id']) && !empty($authInfo['domain'])) {
                $authId = $authInfo['auth_id'];
                $domain = $authInfo['domain'];
            }
        }
        
        // Проверяем external_access
        if ($appData && isset($appData['externalAccessEnabled'])) {
            $externalAccessEnabled = (bool)$appData['externalAccessEnabled'];
        }
        
        // Если токена нет в appData, используем токен из запроса
        if (!$authId || !$domain) {
            $authId = $_POST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_GET['APP_SID'] ?? null;
            $domain = $_POST['DOMAIN'] ?? $_GET['DOMAIN'] ?? null;
        }
        
        $refreshId = $_POST['REFRESH_ID'] ?? $_GET['REFRESH_ID'] ?? null;
        $authExpires = isset($_POST['AUTH_EXPIRES']) 
            ? (int)$_POST['AUTH_EXPIRES'] 
            : (isset($_GET['AUTH_EXPIRES']) ? (int)$_GET['AUTH_EXPIRES'] : null);
        
        // Построение скриптов
        $authScript = $this->buildAuthScript($authId, $domain, $refreshId, $authExpires, $externalAccessEnabled);
        $appDataScript = $this->buildAppDataScript($appData);
        $navigationScript = $this->buildNavigationScript($route);
        
        // Добавление base href для правильной работы ES модулей
        if (strpos($html, '<base') === false) {
            $html = str_replace('<head>', '<head>' . "\n" . '    <base href="/APP-B24/">', $html);
        }
        
        // Вставка скриптов в HTML
        $html = str_replace('</head>', $authScript . $appDataScript . '</head>', $html);
        if ($navigationScript) {
            $html = str_replace('</body>', $navigationScript . '</body>', $html);
        }
        
        // Логирование успешной загрузки
        $this->logger->log('Vue.js app loaded successfully', [
            'route' => $route,
            'has_data' => $appData !== null,
            'has_auth' => !empty($authId) && !empty($domain)
        ], 'info');
        
        // Установка заголовков перед выводом
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        // Вывод HTML
        echo $html;
        exit;
    }
    
    /**
     * Проверка существования файлов Vue.js
     * 
     * @return bool true если файлы существуют
     */
    public function checkVueAppExists(): bool
    {
        $path = realpath($this->vueAppPath);
        return $path !== false && file_exists($path);
    }
    
    /**
     * Получение пути к Vue.js приложению
     * 
     * @return string Путь к index.html
     */
    public function getVueAppPath(): string
    {
        return $this->vueAppPath;
    }
    
    /**
     * Построение скрипта авторизации Bitrix24
     * 
     * @param string|null $authId Токен авторизации
     * @param string|null $domain Домен портала
     * @param string|null $refreshId Refresh токен
     * @param int|null $authExpires Время истечения токена
     * @return string JavaScript код
     */
    protected function buildAuthScript(
        ?string $authId, 
        ?string $domain, 
        ?string $refreshId = null, 
        ?int $authExpires = null,
        bool $externalAccessEnabled = false
    ): string {
        $script = '<script>' . "\n";
        
        if ($authId && $domain) {
            $script .= '        (function() {' . "\n";
            $script .= '            const authData = {' . "\n";
            $script .= '                auth_token: ' . json_encode($authId, JSON_UNESCAPED_UNICODE) . ',' . "\n";
            $script .= '                refresh_token: ' . ($refreshId ? json_encode($refreshId, JSON_UNESCAPED_UNICODE) : 'null') . ',' . "\n";
            $script .= '                expires: ' . ($authExpires ?: 'null') . ',' . "\n";
            $script .= '                domain: ' . json_encode($domain, JSON_UNESCAPED_UNICODE) . "\n";
            $script .= '            };' . "\n";
            $script .= '            sessionStorage.setItem("bitrix24_auth", JSON.stringify(authData));' . "\n";
            $script .= '            console.log("Auth token from PHP saved to sessionStorage");' . "\n";
            $script .= '        })();' . "\n";
        }
        
        $script .= '    </script>' . "\n";
        
        // Загружаем SDK только если не external_access или токен не передан
        // SDK работает только внутри iframe Bitrix24
        if (!$externalAccessEnabled || !$authId) {
            $script .= '    <script src="//api.bitrix24.com/api/v1/"></script>' . "\n";
            $script .= '    <script>' . "\n";
            $script .= '        // Ожидание загрузки Bitrix24 SDK' . "\n";
            $script .= '        (function() {' . "\n";
            $script .= '            let attempts = 0;' . "\n";
            $script .= '            const maxAttempts = 50; // 5 секунд максимум' . "\n";
            $script .= '            function tryInitBitrix24() {' . "\n";
            $script .= '                // Проверяем наличие BX24 и что он не null' . "\n";
            $script .= '                if (typeof BX24 !== "undefined" && BX24 !== null && typeof BX24.init === "function") {' . "\n";
            $script .= '                    try {' . "\n";
            $script .= '                        // Проверяем BX24 перед вызовом' . "\n";
            $script .= '                        if (typeof BX24 === "undefined" || BX24 === null || typeof BX24.init !== "function") {' . "\n";
            $script .= '                            console.warn("Bitrix24 SDK is not available (BX24 is null or undefined)");' . "\n";
            $script .= '                            return false;' . "\n";
            $script .= '                        }' . "\n";
            $script .= '                        ' . "\n";
            $script .= '                        BX24.init(function() {' . "\n";
            $script .= '                            if (!sessionStorage.getItem("bitrix24_auth")) {' . "\n";
            $script .= '                                // Проверяем BX24 перед вызовом getAuth' . "\n";
            $script .= '                                if (typeof BX24 !== "undefined" && BX24 !== null && typeof BX24.getAuth === "function") {' . "\n";
            $script .= '                                    BX24.getAuth(function(auth) {' . "\n";
            $script .= '                                        if (auth && auth.auth_token) {' . "\n";
            $script .= '                                            sessionStorage.setItem("bitrix24_auth", JSON.stringify(auth));' . "\n";
            $script .= '                                            console.log("Bitrix24 auth token retrieved via BX24.getAuth()");' . "\n";
            $script .= '                                        }' . "\n";
            $script .= '                                    });' . "\n";
            $script .= '                                } else {' . "\n";
            $script .= '                                    console.warn("Bitrix24.getAuth() is not available");' . "\n";
            $script .= '                                }' . "\n";
            $script .= '                            }' . "\n";
            $script .= '                        });' . "\n";
            $script .= '                        console.log("Bitrix24 SDK initialized successfully");' . "\n";
            $script .= '                    } catch (e) {' . "\n";
            $script .= '                        console.error("Error initializing Bitrix24 SDK:", e);' . "\n";
            $script .= '                    }' . "\n";
            $script .= '                    return true;' . "\n";
            $script .= '                }' . "\n";
            $script .= '                return false;' . "\n";
            $script .= '            }' . "\n";
            $script .= '            ' . "\n";
            $script .= '            // Пытаемся инициализировать сразу' . "\n";
            $script .= '            if (tryInitBitrix24()) {' . "\n";
            $script .= '                return;' . "\n";
            $script .= '            }' . "\n";
            $script .= '            ' . "\n";
            $script .= '            // Если не получилось, ждём загрузки SDK' . "\n";
            $script .= '            const interval = setInterval(function() {' . "\n";
            $script .= '                attempts++;' . "\n";
            $script .= '                if (tryInitBitrix24() || attempts >= maxAttempts) {' . "\n";
            $script .= '                    clearInterval(interval);' . "\n";
            $script .= '                    if (attempts >= maxAttempts) {' . "\n";
            $script .= '                        console.warn("Bitrix24 SDK not loaded after " + maxAttempts + " attempts. App may work in limited mode.");' . "\n";
            $script .= '                    }' . "\n";
            $script .= '                }' . "\n";
            $script .= '            }, 100);' . "\n";
            $script .= '        })();' . "\n";
            $script .= '    </script>' . "\n";
        } else {
            // SDK не загружается: external_access=true и токен уже передан из PHP
            // SDK работает только внутри iframe Bitrix24
        }
        
        return $script;
    }
    
    /**
     * Построение скрипта передачи данных в Vue.js
     * 
     * @param array|null $appData Данные для передачи
     * @return string JavaScript код
     */
    protected function buildAppDataScript(?array $appData): string
    {
        if ($appData === null || empty($appData)) {
            return '';
        }
        
        // Валидация данных
        if (!is_array($appData)) {
            $this->logger->logError('VueAppService: Invalid appData', [
                'type' => gettype($appData)
            ]);
            return '';
        }
        
        $jsonData = json_encode($appData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            $this->logger->logError('VueAppService: Failed to encode appData', [
                'error' => json_last_error_msg()
            ]);
            return '';
        }
        
        $script = '        (function() {' . "\n";
        $script .= '            const appData = ' . $jsonData . ';' . "\n";
        $script .= '            sessionStorage.setItem("app_data", JSON.stringify(appData));' . "\n";
        $script .= '            window.__APP_DATA__ = appData;' . "\n";
        $script .= '            console.log("App data from PHP saved");' . "\n";
        $script .= '        })();' . "\n";
        
        return '<script>' . "\n" . $script . '    </script>' . "\n";
    }
    
    /**
     * Построение скрипта навигации для Vue Router
     * 
     * @param string $route Начальный маршрут
     * @param string $queryString Query строка
     * @return string JavaScript код или пустая строка
     */
    protected function buildNavigationScript(string $route, string $queryString = ''): string
    {
        if ($route === '/' || empty($route)) {
            return '';
        }
        
        // Получение query строки из текущего URL
        if (empty($queryString)) {
            $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        }
        
        // Убираем параметр route из query, так как он уже в маршруте
        if (!empty($queryString)) {
            $params = [];
            parse_str(ltrim($queryString, '?'), $params);
            unset($params['route']);
            $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        }
        
        $script = '        <script>' . "\n";
        $script .= '            (function() {' . "\n";
        $script .= '                const targetRoute = ' . json_encode($route, JSON_UNESCAPED_UNICODE) . ';' . "\n";
        $script .= '                const queryString = ' . json_encode($queryString, JSON_UNESCAPED_UNICODE) . ';' . "\n";
        $script .= '                const basePath = "/APP-B24";' . "\n";
        $script .= '                const newPath = basePath + targetRoute;' . "\n";
        $script .= '                if (window.history && window.history.pushState) {' . "\n";
        $script .= '                    window.history.replaceState({}, "", newPath + queryString);' . "\n";
        $script .= '                }' . "\n";
        $script .= '                function tryRouterNavigation() {' . "\n";
        $script .= '                    const appElement = document.querySelector("#app");' . "\n";
        $script .= '                    if (appElement && appElement.__vue_app__) {' . "\n";
        $script .= '                        const app = appElement.__vue_app__;' . "\n";
        $script .= '                        if (app.config && app.config.globalProperties && app.config.globalProperties.$router) {' . "\n";
        $script .= '                            const router = app.config.globalProperties.$router;' . "\n";
        $script .= '                            router.push(targetRoute).catch(function(err) {' . "\n";
        $script .= '                                if (err.name !== "NavigationDuplicated") {' . "\n";
        $script .= '                                    console.warn("Router navigation:", err);' . "\n";
        $script .= '                                }' . "\n";
        $script .= '                            });' . "\n";
        $script .= '                            return true;' . "\n";
        $script .= '                        }' . "\n";
        $script .= '                    }' . "\n";
        $script .= '                    return false;' . "\n";
        $script .= '                }' . "\n";
        $script .= '                if (document.readyState === "loading") {' . "\n";
        $script .= '                    document.addEventListener("DOMContentLoaded", function() {' . "\n";
        $script .= '                        setTimeout(function() {' . "\n";
        $script .= '                            if (!tryRouterNavigation()) {' . "\n";
        $script .= '                                setTimeout(tryRouterNavigation, 500);' . "\n";
        $script .= '                            }' . "\n";
        $script .= '                        }, 100);' . "\n";
        $script .= '                    });' . "\n";
        $script .= '                } else {' . "\n";
        $script .= '                    setTimeout(function() {' . "\n";
        $script .= '                        if (!tryRouterNavigation()) {' . "\n";
        $script .= '                            setTimeout(tryRouterNavigation, 500);' . "\n";
        $script .= '                        }' . "\n";
        $script .= '                    }, 100);' . "\n";
        $script .= '                }' . "\n";
        $script .= '            })();' . "\n";
        $script .= '        </script>' . "\n";
        
        return $script;
    }
    
    /**
     * Отображение страницы ошибки
     * 
     * @param string $message Основное сообщение
     * @param string $details Детали ошибки
     * @return void
     */
    protected function renderErrorPage(string $message, string $details = ''): void
    {
        $this->logger->logError('VueAppService: ' . $message, [
            'details' => $details,
            'path' => $this->vueAppPath
        ]);
        
        http_response_code(503);
        
        if ($this->appEnv === 'development') {
            echo '<!DOCTYPE html>' . "\n";
            echo '<html>' . "\n";
            echo '<head>' . "\n";
            echo '    <meta charset="UTF-8">' . "\n";
            echo '    <title>Vue.js приложение не собрано</title>' . "\n";
            echo '    <style>' . "\n";
            echo '        body { font-family: Arial, sans-serif; padding: 40px; text-align: center; }' . "\n";
            echo '        h1 { color: #e74c3c; }' . "\n";
            echo '        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }' . "\n";
            echo '    </style>' . "\n";
            echo '</head>' . "\n";
            echo '<body>' . "\n";
            echo '    <h1>' . htmlspecialchars($message) . '</h1>' . "\n";
            if ($details) {
                echo '    <p>' . htmlspecialchars($details) . '</p>' . "\n";
            }
            echo '    <p>Для запуска приложения необходимо собрать фронтенд:</p>' . "\n";
            echo '    <p><code>cd frontend && npm install && npm run build</code></p>' . "\n";
            echo '    <p>Или запустить dev-сервер:</p>' . "\n";
            echo '    <p><code>cd frontend && npm run dev</code></p>' . "\n";
            echo '</body>' . "\n";
            echo '</html>' . "\n";
        } else {
            echo 'Service temporarily unavailable';
        }
        
        exit;
    }
}

