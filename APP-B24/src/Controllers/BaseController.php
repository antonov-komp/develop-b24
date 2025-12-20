<?php

namespace App\Controllers;

/**
 * Базовый контроллер для всех контроллеров приложения
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class BaseController
{
    protected string $templatesDir;
    
    public function __construct()
    {
        $this->templatesDir = __DIR__ . '/../../templates/';
    }
    
    /**
     * Рендеринг шаблона
     * 
     * @param string $template Имя шаблона (без расширения)
     * @param array $data Данные для передачи в шаблон
     */
    protected function render(string $template, array $data = []): void
    {
        $templateFile = $this->templatesDir . $template . '.php';
        
        if (!file_exists($templateFile)) {
            throw new \Exception("Template not found: {$template}");
        }
        
        // Извлекаем переменные из массива $data
        extract($data);
        
        // Начинаем буферизацию вывода для контента
        ob_start();
        include $templateFile;
        $content = ob_get_clean();
        
        // Извлекаем стили, если они были определены в шаблоне
        $styles = $styles ?? '';
        
        // Рендерим базовый шаблон
        $layoutFile = $this->templatesDir . 'layout.php';
        if (file_exists($layoutFile)) {
            extract(['content' => $content, 'styles' => $styles] + $data);
            include $layoutFile;
        } else {
            echo $content;
        }
    }
    
    /**
     * Редирект на указанный URL
     * 
     * @param string $url URL для редиректа
     */
    protected function redirect(string $url): void
    {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Location: ' . $url, true, 302);
        exit;
    }
    
    /**
     * Вывод JSON-ответа
     * 
     * @param array $data Данные для вывода
     */
    protected function json(array $data): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Получение параметра запроса
     * 
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed Значение параметра
     */
    protected function getRequestParam(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }
}

