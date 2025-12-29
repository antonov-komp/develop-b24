<?php

namespace App\Services;

/**
 * Сервис определения режима доступа
 * 
 * Определяет режим доступа на основе конфигурации и запроса
 * Документация: https://context7.com/bitrix24/rest/
 */
class AccessModeService
{
    protected ConfigService $configService;
    protected LoggerService $logger;
    
    public function __construct(ConfigService $configService, LoggerService $logger)
    {
        $this->configService = $configService;
        $this->logger = $logger;
    }
    
    /**
     * Определение режима доступа на основе конфигурации
     * 
     * Режимы:
     * 1. Только Bitrix24: external_access=false
     * 2. Везде: external_access=true, block_bitrix24_iframe=false
     * 3. Только внешний: external_access=true, block_bitrix24_iframe=true
     * 
     * @param array $config Конфигурация главной страницы
     * @return array Режим доступа с полями:
     *   - external_access_enabled: bool
     *   - block_bitrix24_iframe: bool
     *   - mode: string (bitrix24_only|everywhere|external_only)
     */
    public function determineAccessMode(array $config): array
    {
        $externalAccessEnabled = isset($config['external_access']) && $config['external_access'] === true;
        $blockBitrix24Iframe = isset($config['block_bitrix24_iframe']) && $config['block_bitrix24_iframe'] === true;
        
        // Определяем режим
        $mode = 'bitrix24_only';
        if ($externalAccessEnabled && !$blockBitrix24Iframe) {
            $mode = 'everywhere';
        } elseif ($externalAccessEnabled && $blockBitrix24Iframe) {
            $mode = 'external_only';
        }
        
        $this->logger->log('AccessModeService: Access mode determined', [
            'external_access_enabled' => $externalAccessEnabled,
            'block_bitrix24_iframe' => $blockBitrix24Iframe,
            'mode' => $mode
        ], 'info');
        
        return [
            'external_access_enabled' => $externalAccessEnabled,
            'block_bitrix24_iframe' => $blockBitrix24Iframe,
            'mode' => $mode
        ];
    }
    
    /**
     * Проверка внешнего доступа
     * 
     * @param array $accessMode Режим доступа
     * @return bool true если внешний доступ включен
     */
    public function checkExternalAccess(array $accessMode): bool
    {
        return $accessMode['external_access_enabled'] ?? false;
    }
    
    /**
     * Проверка блокировки Bitrix24 iframe
     * 
     * @param array $accessMode Режим доступа
     * @return bool true если Bitrix24 iframe заблокирован
     */
    public function checkBitrix24IframeBlocked(array $accessMode): bool
    {
        return $accessMode['block_bitrix24_iframe'] ?? false;
    }
    
    /**
     * Проверка наличия токена пользователя в запросе
     * 
     * @return bool true если есть токен пользователя (AUTH_ID и DOMAIN)
     */
    public function hasUserTokenInRequest(): bool
    {
        return !empty($_REQUEST['AUTH_ID']) && !empty($_REQUEST['DOMAIN']);
    }
}

