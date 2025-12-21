<?php

namespace App\Exceptions;

/**
 * Исключение для ошибок конфигурации
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class ConfigException extends \Exception
{
    /**
     * @param string $message Сообщение об ошибке
     * @param int $code Код ошибки
     * @param \Throwable|null $previous Предыдущее исключение
     */
    public function __construct(
        string $message = "Ошибка конфигурации",
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}






