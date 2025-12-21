<?php

namespace App\Helpers;

use App\Services\ConfigService;

/**
 * Вспомогательный класс для получения домена портала
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class DomainResolver
{
    protected ConfigService $configService;
    
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }
    
    /**
     * Получение домена портала
     * 
     * Приоритет:
     * 1. Домен из параметров запроса (DOMAIN)
     * 2. Домен из client_endpoint в settings.json
     * 3. Домен из settings.json (domain)
     * 
     * @return string|null Домен портала или null
     */
    public function resolveDomain(): ?string
    {
        // Приоритет 1: Домен из параметров запроса
        if (isset($_REQUEST['DOMAIN']) && !empty($_REQUEST['DOMAIN'])) {
            return $_REQUEST['DOMAIN'];
        }
        
        // Приоритет 2: Домен из client_endpoint в settings.json
        $settings = $this->configService->getSettings();
        if (isset($settings['client_endpoint']) && !empty($settings['client_endpoint'])) {
            $clientEndpoint = $settings['client_endpoint'];
            if (preg_match('#https?://([^/]+)#', $clientEndpoint, $matches)) {
                return $matches[1];
            }
        }
        
        // Приоритет 3: Домен из settings.json
        if (isset($settings['domain']) && !empty($settings['domain'])) {
            $domainFromSettings = $settings['domain'];
            // Исключаем oauth.bitrix.info - это не домен портала
            if ($domainFromSettings !== 'oauth.bitrix.info') {
                return $domainFromSettings;
            }
        }
        
        return null;
    }
}







