<?php

namespace App\Exceptions;

/**
 * Исключение для ошибок Bitrix24 API
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24ApiException extends \Exception
{
    protected ?string $apiError;
    protected ?string $apiErrorDescription;
    
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $apiError = null,
        ?string $apiErrorDescription = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->apiError = $apiError;
        $this->apiErrorDescription = $apiErrorDescription;
    }
    
    /**
     * Получение кода ошибки API
     * 
     * @return string|null Код ошибки API
     */
    public function getApiError(): ?string
    {
        return $this->apiError;
    }
    
    /**
     * Получение описания ошибки API
     * 
     * @return string|null Описание ошибки API
     */
    public function getApiErrorDescription(): ?string
    {
        return $this->apiErrorDescription;
    }
}



