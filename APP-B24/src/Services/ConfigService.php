<?php

namespace App\Services;

/**
 * Сервис для работы с конфигурационными файлами
 * 
 * Управляет конфигурацией главной страницы (config.json),
 * правами доступа (access-config.json) и настройками приложения (settings.json)
 * Документация: https://context7.com/bitrix24/rest/
 */
class ConfigService
{
    protected string $configDir;
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->configDir = __DIR__ . '/../../';
        $this->logger = $logger;
    }
    
    /**
     * Получение конфигурации главной страницы
     * 
     * Читает config.json и проверяет, включен ли интерфейс приложения.
     * При ошибках чтения/парсинга использует безопасный режим (enabled: true).
     * 
     * @return array Результат проверки конфига
     *   - 'enabled' (bool) — доступен ли интерфейс
     *   - 'message' (string|null) — сообщение при деактивации
     *   - 'last_updated' (string|null) — дата последнего обновления
     */
    public function getIndexPageConfig(): array
    {
        $configFile = $this->configDir . 'config.json';
        $defaultConfig = [
            'enabled' => true,
            'message' => null,
            'last_updated' => null
        ];
        
        // Получаем информацию о пользователе для логирования
        $userId = $_REQUEST['AUTH_ID'] ?? 'unknown';
        $domain = $_REQUEST['DOMAIN'] ?? 'unknown';
        $context = [
            'user_id' => $userId !== 'unknown' ? substr($userId, 0, 20) . '...' : 'unknown',
            'domain' => $domain
        ];
        
        // Если файл отсутствует — используем значения по умолчанию
        if (!file_exists($configFile)) {
            $this->logger->logConfigCheck('CONFIG CHECK ERROR: Config file not found, using default (enabled=true)', $context);
            return $defaultConfig;
        }
        
        // Читаем файл конфига
        $configContent = @file_get_contents($configFile);
        if ($configContent === false) {
            $this->logger->logConfigCheck('CONFIG CHECK ERROR: Failed to read config.json, using default (enabled=true)', $context);
            return $defaultConfig;
        }
        
        // Парсим JSON
        $config = @json_decode($configContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->logConfigCheck('CONFIG CHECK ERROR: Failed to parse config.json: ' . json_last_error_msg() . ', using default (enabled=true)', $context);
            return $defaultConfig;
        }
        
        // Проверяем наличие секции index_page
        if (!isset($config['index_page']) || !is_array($config['index_page'])) {
            $this->logger->logConfigCheck('CONFIG CHECK ERROR: Section "index_page" not found in config.json, using default (enabled=true)', $context);
            return $defaultConfig;
        }
        
        $indexPageConfig = $config['index_page'];
        
        // Проверяем значение enabled
        $enabled = isset($indexPageConfig['enabled']) 
            ? (bool)$indexPageConfig['enabled'] 
            : true; // По умолчанию включено
        
        $message = $indexPageConfig['message'] ?? null;
        $lastUpdated = $indexPageConfig['last_updated'] ?? null;
        
        // Логируем результат проверки
        $logMessage = sprintf(
            'CONFIG CHECK: enabled=%s, message=%s, last_updated=%s',
            $enabled ? 'true' : 'false',
            $message ? '"' . $message . '"' : 'null',
            $lastUpdated ?? 'null'
        );
        $this->logger->logConfigCheck($logMessage, $context);
        
        return [
            'enabled' => $enabled,
            'message' => $message,
            'last_updated' => $lastUpdated
        ];
    }
    
    /**
     * Получение конфигурации прав доступа
     * 
     * @return array Конфигурация прав доступа
     */
    public function getAccessConfig(): array
    {
        $configFile = $this->configDir . 'access-config.json';
        $defaultConfig = [
            'access_control' => [
                'enabled' => true,
                'departments' => [],
                'users' => [],
                'last_updated' => null,
                'updated_by' => null
            ]
        ];
        
        if (!file_exists($configFile)) {
            return $defaultConfig;
        }
        
        $configContent = @file_get_contents($configFile);
        if ($configContent === false) {
            return $defaultConfig;
        }
        
        $config = @json_decode($configContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $defaultConfig;
        }
        
        // Убеждаемся, что структура правильная
        if (!isset($config['access_control'])) {
            return $defaultConfig;
        }
        
        return $config;
    }
    
    /**
     * Сохранение конфигурации прав доступа
     * 
     * @param array $config Конфигурация для сохранения
     * @return array Результат операции ['success' => bool, 'error' => string|null]
     */
    public function saveAccessConfig(array $config): array
    {
        $configFile = $this->configDir . 'access-config.json';
        
        // Убеждаемся, что структура правильная
        if (!isset($config['access_control'])) {
            $error = 'Неверная структура конфигурации: отсутствует секция access_control';
            $this->logger->logAccessConfigSaveError($error, [
                'config_keys' => array_keys($config)
            ]);
            return ['success' => false, 'error' => $error];
        }
        
        // Убеждаемся, что все необходимые поля инициализированы
        if (!isset($config['access_control']['departments']) || !is_array($config['access_control']['departments'])) {
            $config['access_control']['departments'] = [];
        }
        if (!isset($config['access_control']['users']) || !is_array($config['access_control']['users'])) {
            $config['access_control']['users'] = [];
        }
        
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            $error = 'Ошибка кодирования JSON: ' . json_last_error_msg();
            $this->logger->logAccessConfigSaveError($error, [
                'config_structure' => print_r($config, true)
            ]);
            return ['success' => false, 'error' => $error];
        }
        
        // Проверяем права на запись
        if (!is_writable($configFile) && file_exists($configFile)) {
            $error = 'Файл конфигурации недоступен для записи. Проверьте права доступа.';
            $this->logger->logAccessConfigSaveError($error, [
                'file' => $configFile,
                'owner' => fileowner($configFile),
                'perms' => substr(sprintf('%o', fileperms($configFile)), -4)
            ]);
            return ['success' => false, 'error' => $error];
        }
        
        // Проверяем права на директорию, если файл не существует
        if (!file_exists($configFile)) {
            $dir = dirname($configFile);
            if (!is_writable($dir)) {
                $error = 'Директория недоступна для записи. Проверьте права доступа.';
                $this->logger->logAccessConfigSaveError($error, [
                    'directory' => $dir
                ]);
                return ['success' => false, 'error' => $error];
            }
        }
        
        // Пробуем сохранить файл
        // Сначала пробуем с блокировкой
        $result = @file_put_contents($configFile, $json, LOCK_EX);
        
        // Если не получилось, пробуем без блокировки
        if ($result === false) {
            $result = @file_put_contents($configFile, $json);
        }
        
        if ($result === false) {
            $error = 'Ошибка записи файла. Проверьте права доступа и место на диске.';
            $lastError = error_get_last();
            if ($lastError) {
                $error .= ' (' . $lastError['message'] . ')';
            }
            
            // Детальное логирование ошибки
            $errorLog = [
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $error,
                'file' => $configFile,
                'file_exists' => file_exists($configFile),
                'is_writable' => is_writable($configFile),
                'is_dir_writable' => is_writable(dirname($configFile)),
                'file_perms' => file_exists($configFile) ? substr(sprintf('%o', fileperms($configFile)), -4) : 'N/A',
                'file_owner' => file_exists($configFile) ? fileowner($configFile) : 'N/A',
                'file_group' => file_exists($configFile) ? filegroup($configFile) : 'N/A',
                'current_user' => get_current_user(),
                'process_user' => function_exists('posix_geteuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown') : 'unknown',
                'disk_free_space' => disk_free_space(dirname($configFile)),
                'json_length' => strlen($json),
                'last_error' => $lastError
            ];
            
            $this->logger->logAccessConfigSaveError($error, $errorLog);
            
            return ['success' => false, 'error' => $error];
        }
        
        // Логируем успешное сохранение
        $this->logger->logAccessConfigSaveSuccess([
            'file' => $configFile,
            'bytes' => $result
        ]);
        
        return ['success' => true, 'error' => null];
    }
    
    /**
     * Получение настроек приложения
     * 
     * @return array Настройки приложения (токены, домен и т.д.)
     */
    public function getSettings(): array
    {
        $settingsFile = $this->configDir . 'settings.json';
        $defaultSettings = [
            'access_token' => null,
            'domain' => null,
            'client_endpoint' => null
        ];
        
        if (!file_exists($settingsFile)) {
            return $defaultSettings;
        }
        
        $settingsContent = @file_get_contents($settingsFile);
        if ($settingsContent === false) {
            return $defaultSettings;
        }
        
        $settings = @json_decode($settingsContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $defaultSettings;
        }
        
        return array_merge($defaultSettings, $settings);
    }
}


