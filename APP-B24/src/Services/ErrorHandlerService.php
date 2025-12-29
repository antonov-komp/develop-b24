<?php

namespace App\Services;

/**
 * Сервис обработки ошибок
 * 
 * Объединяет логику из handleFatalError() (index.php) и VueAppService::renderErrorPage()
 * Документация: https://context7.com/bitrix24/rest/
 */
class ErrorHandlerService
{
    protected LoggerService $logger;
    protected string $configErrorTemplatePath;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->configErrorTemplatePath = __DIR__ . '/../../templates/config-error.php';
    }
    
    /**
     * Обработка фатальных ошибок
     * 
     * Вынесено из функции handleFatalError() в index.php (строки 562-589)
     * 
     * ВАЖНО: Это единственное место, где PHP генерирует HTML.
     * Используется только для критических ошибок, когда Vue.js не может загрузиться.
     * 
     * @param \Throwable $e Исключение
     * @param string $appEnv Окружение (development/production)
     * @return void
     */
    public function handleFatalError(\Throwable $e, string $appEnv): void
    {
        error_log('Fatal error in index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        
        $this->logError($e, 'fatal');
        
        http_response_code(500);
        $this->renderErrorPage(
            'Ошибка приложения',
            'Произошла критическая ошибка при загрузке страницы.',
            $appEnv === 'development' || ini_get('display_errors') ? $e : null
        );
    }
    
    /**
     * Универсальный метод отображения страницы ошибки
     * 
     * Адаптировано из VueAppService::renderErrorPage() (строки 370-407)
     * 
     * @param string $title Заголовок ошибки
     * @param string $message Сообщение об ошибке
     * @param \Throwable|null $exception Исключение (для development режима)
     * @return void
     */
    public function renderErrorPage(string $title, string $message, ?\Throwable $exception = null): void
    {
        $appEnv = getenv('APP_ENV') ?: 'production';
        $showDetails = $appEnv === 'development' || ini_get('display_errors');
        
        http_response_code(500);
        
        if ($showDetails && $exception) {
            echo '<!DOCTYPE html>' . "\n";
            echo '<html>' . "\n";
            echo '<head>' . "\n";
            echo '    <meta charset="UTF-8">' . "\n";
            echo '    <title>' . htmlspecialchars($title) . '</title>' . "\n";
            echo '    <style>' . "\n";
            echo '        body { font-family: Arial, sans-serif; padding: 40px; }' . "\n";
            echo '        h1 { color: #e74c3c; }' . "\n";
            echo '        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }' . "\n";
            echo '        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }' . "\n";
            echo '    </style>' . "\n";
            echo '</head>' . "\n";
            echo '<body>' . "\n";
            echo '    <h1>' . htmlspecialchars($title) . '</h1>' . "\n";
            echo '    <p>' . htmlspecialchars($message) . '</p>' . "\n";
            echo '    <pre>' . htmlspecialchars($exception->getMessage()) . '</pre>' . "\n";
            echo '    <pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>' . "\n";
            echo '</body>' . "\n";
            echo '</html>' . "\n";
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title></head><body>';
            echo '<h1>' . htmlspecialchars($title) . '</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            echo '</body></html>';
        }
        
        exit;
    }
    
    /**
     * Отображение страницы ошибки конфигурации
     * 
     * Использует шаблон templates/config-error.php
     * 
     * @param array $config Конфигурация с полями message и last_updated
     * @return void
     */
    public function renderConfigErrorPage(array $config): void
    {
        $message = $config['message'] ?? 'Интерфейс приложения временно недоступен. Пожалуйста, попробуйте позже.';
        $lastUpdated = $config['last_updated'] ?? null;
        
        $this->logger->log('ErrorHandlerService: Rendering config error page', [
            'message' => $message,
            'last_updated' => $lastUpdated
        ], 'info');
        
        // Используем существующий шаблон
        if (file_exists($this->configErrorTemplatePath)) {
            // Сохраняем оригинальные GET-параметры
            $originalGet = $_GET;
            
            // Устанавливаем параметры для шаблона
            $_GET['message'] = $message;
            if ($lastUpdated) {
                $_GET['last_updated'] = $lastUpdated;
            }
            
            // Подключаем шаблон
            require_once($this->configErrorTemplatePath);
            
            // Восстанавливаем оригинальные GET-параметры
            $_GET = $originalGet;
        } else {
            // Fallback если шаблон не найден
            http_response_code(503);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Интерфейс недоступен</title></head><body>';
            echo '<h1>Интерфейс недоступен</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            if ($lastUpdated) {
                echo '<p><small>Последнее обновление: ' . htmlspecialchars($lastUpdated) . '</small></p>';
            }
            echo '</body></html>';
        }
        
        exit;
    }
    
    /**
     * Логирование ошибок
     * 
     * @param \Throwable $e Исключение
     * @param string $type Тип ошибки (fatal, error, warning)
     * @return void
     */
    public function logError(\Throwable $e, string $type = 'error'): void
    {
        $this->logger->logError('ErrorHandlerService: ' . $type . ' error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

