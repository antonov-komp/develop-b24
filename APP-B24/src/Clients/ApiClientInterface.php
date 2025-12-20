<?php

namespace App\Clients;

/**
 * Интерфейс для клиентов Bitrix24 API
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
interface ApiClientInterface
{
    /**
     * Вызов метода Bitrix24 REST API
     * 
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ от API
     */
    public function call(string $method, array $params = []): array;
    
    /**
     * Батч-запросы к Bitrix24 REST API
     * 
     * @param array $commands Массив команд для выполнения
     * @param int $halt Остановка при ошибке (0 или 1)
     * @return array Ответ от API
     */
    public function callBatch(array $commands, int $halt = 0): array;
}

