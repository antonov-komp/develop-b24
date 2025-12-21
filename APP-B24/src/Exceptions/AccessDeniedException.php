<?php

namespace App\Exceptions;

/**
 * Исключение для ошибок доступа
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class AccessDeniedException extends \Exception
{
    /**
     * @param string $message Сообщение об ошибке
     * @param int $code Код ошибки
     * @param \Throwable|null $previous Предыдущее исключение
     */
    public function __construct(
        string $message = "Доступ запрещён",
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}







