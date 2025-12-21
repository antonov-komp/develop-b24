<?php

namespace App\Services;

/**
 * Сервис для унифицированного логирования
 * 
 * Обеспечивает единый интерфейс для логирования во всём приложении
 * Документация: https://context7.com/bitrix24/rest/
 */
class LoggerService
{
    protected string $logsDir;
    
    public function __construct()
    {
        $this->logsDir = __DIR__ . '/../../logs/';
        
        // Создаём директорию логов, если её нет
        if (!is_dir($this->logsDir)) {
            @mkdir($this->logsDir, 0755, true);
        }
    }
    
    /**
     * Общее логирование
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $type Тип лога (info, error, warning)
     */
    public function log(string $message, array $context = [], string $type = 'info'): void
    {
        $logFile = $this->logsDir . $type . '-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
        
        if (!empty($context)) {
            $logEntry .= ', ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Логирование ошибок
     * 
     * @param string $message Сообщение об ошибке
     * @param array $context Дополнительный контекст
     */
    public function logError(string $message, array $context = []): void
    {
        $this->log($message, $context, 'error');
    }
    
    /**
     * Логирование проверки доступа
     * 
     * @param int $userId ID пользователя
     * @param array $userDepartments Массив ID отделов пользователя
     * @param string $result Результат проверки (granted/denied)
     * @param string $reason Причина результата
     */
    public function logAccessCheck(int $userId, array $userDepartments, string $result, string $reason): void
    {
        $logFile = $this->logsDir . 'access-check-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ACCESS CHECK: user_id=' . $userId . 
            ', departments=' . json_encode($userDepartments, JSON_UNESCAPED_UNICODE) . 
            ', result=' . $result . 
            ', reason=' . $reason . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Логирование проверки конфигурации
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     */
    public function logConfigCheck(string $message, array $context = []): void
    {
        $logFile = $this->logsDir . 'config-check-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
        
        if (!empty($context)) {
            $logEntry .= ', ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Логирование проверки авторизации
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     */
    public function logAuthCheck(string $message, array $context = []): void
    {
        $logFile = $this->logsDir . 'auth-check-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
        
        if (!empty($context)) {
            $logEntry .= ', ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Логирование управления правами доступа
     * 
     * @param string $action Действие (add_department, remove_department, add_user, remove_user, toggle)
     * @param array $context Дополнительный контекст
     */
    public function logAccessControl(string $action, array $context = []): void
    {
        $logFile = $this->logsDir . 'access-control-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ACTION: ' . $action;
        
        if (!empty($context)) {
            $logEntry .= ', ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Логирование успешного сохранения конфигурации доступа
     * 
     * @param array $context Дополнительный контекст
     */
    public function logAccessConfigSaveSuccess(array $context = []): void
    {
        $logFile = $this->logsDir . 'access-config-save-success-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - SUCCESS';
        
        if (!empty($context)) {
            $logEntry .= ', ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Логирование ошибки сохранения конфигурации доступа
     * 
     * @param string $error Сообщение об ошибке
     * @param array $context Дополнительный контекст
     */
    public function logAccessConfigSaveError(string $error, array $context = []): void
    {
        $logFile = $this->logsDir . 'access-config-save-error-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ERROR: ' . $error;
        
        if (!empty($context)) {
            $logEntry .= ', ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}





