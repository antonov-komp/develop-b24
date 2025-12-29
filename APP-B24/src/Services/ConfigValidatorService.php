<?php

namespace App\Services;

/**
 * Сервис валидации конфигурации
 * 
 * Переиспользует ConfigService::getIndexPageConfig() и шаблон templates/config-error.php
 * Документация: https://context7.com/bitrix24/rest/
 */
class ConfigValidatorService
{
    protected ConfigService $configService;
    protected LoggerService $logger;
    protected string $configErrorTemplatePath;
    
    public function __construct(ConfigService $configService, LoggerService $logger)
    {
        $this->configService = $configService;
        $this->logger = $logger;
        $this->configErrorTemplatePath = __DIR__ . '/../../templates/config-error.php';
    }
    
    /**
     * Валидация конфигурации главной страницы
     * 
     * Переиспользует ConfigService::getIndexPageConfig()
     * 
     * @return array Результат валидации (конфигурация)
     */
    public function validateIndexPageConfig(): array
    {
        return $this->configService->getIndexPageConfig();
    }
    
    /**
     * Проверка, включено ли приложение
     * 
     * @return bool true если приложение включено
     */
    public function checkAppEnabled(): bool
    {
        $config = $this->validateIndexPageConfig();
        return $config['enabled'] ?? true;
    }
    
    /**
     * Отображение страницы ошибки конфигурации
     * 
     * Переиспользует шаблон templates/config-error.php
     * 
     * @param array $config Конфигурация с полями message и last_updated
     * @return void
     */
    public function renderConfigErrorPage(array $config): void
    {
        $message = $config['message'] ?? 'Интерфейс приложения временно недоступен. Пожалуйста, попробуйте позже.';
        $lastUpdated = $config['last_updated'] ?? null;
        
        $this->logger->log('ConfigValidatorService: Rendering config error page', [
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
}

