<?php

namespace App\Services;

/**
 * Сервис работы с маршрутами
 * 
 * Переиспользует и адаптирует логику из api/index.php
 * Документация: https://context7.com/bitrix24/rest/
 */
class RouteService
{
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Получение маршрута из запроса
     * 
     * Адаптировано из api/index.php (строки 25-61)
     * Упрощено для index.php (не нужны subRoute и segments)
     * 
     * @return string Нормализованный маршрут
     */
    public function getRoute(): string
    {
        // Пробуем получить маршрут из query параметров
        $route = $_GET['route'] ?? null;
        
        // Если маршрут не в query, пытаемся извлечь из REQUEST_URI
        if (!$route) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $path = parse_url($requestUri, PHP_URL_PATH) ?: '';
            
            // Удаляем префиксы для index.php
            $path = preg_replace('#^/APP-B24/index\.php#', '', $path);
            $path = preg_replace('#^/APP-B24#', '', $path);
            $path = preg_replace('#^/index\.php#', '', $path);
            
            $segments = array_filter(explode('/', trim($path, '/')));
            $route = $segments[0] ?? '/';
        }
        
        // Нормализация маршрута
        return $this->normalizeRoute($route);
    }
    
    /**
     * Нормализация маршрута
     * 
     * Убирает лишние слеши и приводит к стандартному виду
     * 
     * @param string $route Маршрут для нормализации
     * @return string Нормализованный маршрут
     */
    public function normalizeRoute(string $route): string
    {
        $route = '/' . trim($route, '/');
        if ($route === '//') {
            $route = '/';
        }
        
        $this->logger->log('RouteService: Route normalized', [
            'normalized' => $route
        ], 'debug');
        
        return $route;
    }
}

