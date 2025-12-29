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
    
    // Кеширование конфигурации прав доступа
    protected static ?array $accessConfigCache = null;
    protected static ?int $accessConfigCacheTime = null;
    protected static ?int $accessConfigFileMtime = null;
    protected int $cacheTtl = 60; // TTL кеша в секундах
    
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
        // Значения по умолчанию (используются только если config.json не найден или повреждён)
        // Безопасный режим по умолчанию: только Bitrix24 (требуется авторизация)
        $defaultConfig = [
            'enabled' => true,
            'external_access' => false,  // По умолчанию только через Bitrix24
            'block_bitrix24_iframe' => false,  // По умолчанию разрешён доступ из Bitrix24
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
        
        // Получаем external_access из конфига
        $externalAccess = isset($indexPageConfig['external_access']) 
            ? (bool)$indexPageConfig['external_access'] 
            : false; // По умолчанию выключен (требуется авторизация)
        
        // Получаем block_bitrix24_iframe из конфига
        $blockBitrix24Iframe = isset($indexPageConfig['block_bitrix24_iframe']) 
            ? (bool)$indexPageConfig['block_bitrix24_iframe'] 
            : false; // По умолчанию разрешён доступ из Bitrix24 iframe
        
        return [
            'enabled' => $enabled,
            'external_access' => $externalAccess,
            'block_bitrix24_iframe' => $blockBitrix24Iframe,
            'message' => $message,
            'last_updated' => $lastUpdated
        ];
    }
    
    /**
     * Получение конфигурации прав доступа
     * 
     * Использует кеширование для оптимизации производительности.
     * Кеш инвалидируется при изменении файла (mtime) или истечении TTL.
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
        
        $currentMtime = file_exists($configFile) ? filemtime($configFile) : 0;
        
        // Проверка кеша: TTL + время модификации файла
        if (self::$accessConfigCache !== null && 
            self::$accessConfigCacheTime !== null &&
            self::$accessConfigFileMtime !== null &&
            (time() - self::$accessConfigCacheTime) < $this->cacheTtl &&
            self::$accessConfigFileMtime === $currentMtime) {
            $this->logger->log('ConfigService: Using cached access config', [
                'cache_age' => time() - self::$accessConfigCacheTime,
                'file_mtime' => $currentMtime
            ], 'debug');
            return self::$accessConfigCache;
        }
        
        // Чтение из файла
        $this->logger->log('ConfigService: Reading access config from file', [
            'file' => $configFile,
            'file_exists' => file_exists($configFile),
            'file_mtime' => $currentMtime
        ], 'debug');
        
        if (!file_exists($configFile)) {
            self::$accessConfigCache = $defaultConfig;
            self::$accessConfigCacheTime = time();
            self::$accessConfigFileMtime = 0;
            return $defaultConfig;
        }
        
        $configContent = @file_get_contents($configFile);
        if ($configContent === false) {
            self::$accessConfigCache = $defaultConfig;
            self::$accessConfigCacheTime = time();
            self::$accessConfigFileMtime = $currentMtime;
            return $defaultConfig;
        }
        
        $config = @json_decode($configContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::$accessConfigCache = $defaultConfig;
            self::$accessConfigCacheTime = time();
            self::$accessConfigFileMtime = $currentMtime;
            return $defaultConfig;
        }
        
        // Убеждаемся, что структура правильная
        if (!isset($config['access_control'])) {
            self::$accessConfigCache = $defaultConfig;
            self::$accessConfigCacheTime = time();
            self::$accessConfigFileMtime = $currentMtime;
            return $defaultConfig;
        }
        
        // Сохранение в кеш
        self::$accessConfigCache = $config;
        self::$accessConfigCacheTime = time();
        self::$accessConfigFileMtime = $currentMtime;
        
        return $config;
    }
    
    /**
     * Очистка кеша конфигурации прав доступа
     * 
     * Вызывается автоматически при сохранении конфигурации.
     * Может быть вызвана вручную для принудительной инвалидации кеша.
     */
    public function clearAccessConfigCache(): void
    {
        self::$accessConfigCache = null;
        self::$accessConfigCacheTime = null;
        self::$accessConfigFileMtime = null;
        $this->logger->log('ConfigService: Access config cache cleared', [], 'debug');
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
        
        // Пытаемся получить блокировку с retry механизмом
        $maxRetries = 3;
        $lockTimeout = 5; // секунд
        $fp = null;
        $locked = false;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $fp = @fopen($configFile, 'c+');
            if (!$fp) {
                if ($attempt < $maxRetries) {
                    usleep(100000 * $attempt); // Экспоненциальный backoff
                    continue;
                }
                $error = 'Не удалось открыть файл для записи';
                $this->logger->logAccessConfigSaveError($error, [
                    'file' => $configFile,
                    'attempts' => $attempt
                ]);
                return ['success' => false, 'error' => $error];
            }
            
            // Пытаемся получить эксклюзивную блокировку
            $lockStart = time();
            $locked = false;
            
            while ((time() - $lockStart) < $lockTimeout) {
                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    $locked = true;
                    break;
                }
                usleep(100000 * $attempt); // Экспоненциальный backoff
            }
            
            if ($locked) {
                break;
            }
            
            fclose($fp);
            $fp = null;
            
            if ($attempt < $maxRetries) {
                $this->logger->log('ConfigService: Lock retry attempt', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries
                ], 'debug');
                usleep(200000 * $attempt); // Задержка перед следующей попыткой
            }
        }
        
        if (!$fp || !$locked) {
            if ($fp) {
                fclose($fp);
            }
            $error = 'Не удалось получить блокировку файла после ' . $maxRetries . ' попыток';
            $this->logger->logAccessConfigSaveError($error, [
                'file' => $configFile,
                'attempts' => $maxRetries
            ]);
            return ['success' => false, 'error' => $error];
        }
        
        try {
            // Запись в файл
            ftruncate($fp, 0);
            rewind($fp);
            $bytesWritten = fwrite($fp, $json);
            fflush($fp);
            
            // Синхронизация на диск (если доступно)
            if (function_exists('fsync')) {
                fsync($fp);
            }
            
            if ($bytesWritten === false || $bytesWritten !== strlen($json)) {
                $error = 'Ошибка записи в файл';
                $this->logger->logAccessConfigSaveError($error, [
                    'file' => $configFile,
                    'expected_bytes' => strlen($json),
                    'written_bytes' => $bytesWritten === false ? 0 : $bytesWritten
                ]);
                return ['success' => false, 'error' => $error];
            }
            
            // Очистка кеша после успешного сохранения
            $this->clearAccessConfigCache();
            
            // Логируем успешное сохранение
            $this->logger->logAccessConfigSaveSuccess([
                'file' => $configFile,
                'bytes' => $bytesWritten,
                'attempts' => $attempt
            ]);
            
            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            $this->logger->logAccessConfigSaveError('Exception during file write', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'error' => 'Ошибка записи: ' . $e->getMessage()];
        } finally {
            // Снятие блокировки
            if ($fp) {
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
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
    
    /**
     * Получение конфигурации логирования
     * 
     * Читает config.json и возвращает конфигурацию логирования.
     * При ошибках чтения/парсинга использует значения по умолчанию.
     * 
     * @return array Конфигурация логирования
     *   - 'enabled' (bool) — включено ли логирование
     *   - 'default_level' (string) — уровень по умолчанию (debug, info, warn, error)
     *   - 'layers' (array) — настройки слоёв логирования
     */
    public function getLoggingConfig(): array
    {
        $configFile = $this->configDir . 'config.json';
        
        // Значения по умолчанию
        $defaultConfig = [
            'enabled' => true,
            'default_level' => 'info',
            'layers' => [
                'INIT' => ['enabled' => true, 'level' => 'info'],
                'ROUTER' => ['enabled' => true, 'level' => 'debug'],
                'VUE_LIFECYCLE' => ['enabled' => true, 'level' => 'info'],
                'USER_STORE' => ['enabled' => true, 'level' => 'debug'],
                'ACCESS_CONTROL' => ['enabled' => true, 'level' => 'debug'],
                'API' => ['enabled' => true, 'level' => 'debug'],
                'BITRIX24' => ['enabled' => true, 'level' => 'info'],
                'ERROR' => ['enabled' => true, 'level' => 'error']
            ]
        ];
        
        // Если файл отсутствует — используем значения по умолчанию
        if (!file_exists($configFile)) {
            return $defaultConfig;
        }
        
        // Читаем файл конфига
        $configContent = @file_get_contents($configFile);
        if ($configContent === false) {
            return $defaultConfig;
        }
        
        // Парсим JSON
        $config = @json_decode($configContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $defaultConfig;
        }
        
        // Проверяем наличие секции logging
        if (!isset($config['logging']) || !is_array($config['logging'])) {
            return $defaultConfig;
        }
        
        $loggingConfig = $config['logging'];
        
        // Объединяем с значениями по умолчанию
        $result = [
            'enabled' => isset($loggingConfig['enabled']) 
                ? (bool)$loggingConfig['enabled'] 
                : $defaultConfig['enabled'],
            'default_level' => $loggingConfig['default_level'] ?? $defaultConfig['default_level'],
            'layers' => array_merge($defaultConfig['layers'], $loggingConfig['layers'] ?? [])
        ];
        
        // Объединяем настройки каждого слоя
        if (isset($loggingConfig['layers']) && is_array($loggingConfig['layers'])) {
            foreach ($loggingConfig['layers'] as $layer => $layerConfig) {
                if (isset($result['layers'][$layer])) {
                    $result['layers'][$layer] = array_merge(
                        $result['layers'][$layer],
                        $layerConfig
                    );
                } else {
                    $result['layers'][$layer] = $layerConfig;
                }
            }
        }
        
        return $result;
    }
}







