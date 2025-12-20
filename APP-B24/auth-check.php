<?php
/**
 * Проверка авторизации Bitrix24
 * 
 * Проверяет, что запрос приходит из Bitrix24 с активной авторизацией
 * Используется для защиты index.php и install.php от прямого доступа
 * 
 * Теперь использует AuthService для проверки авторизации
 * Документация: https://context7.com/bitrix24/rest/
 */

// Подключение и инициализация сервисов
require_once(__DIR__ . '/src/bootstrap.php');

/**
 * Проверка авторизации Bitrix24
 * 
 * Обертка для обратной совместимости
 * 
 * @return bool true если авторизация валидна, false в противном случае
 */
function checkBitrix24Auth()
{
    global $authService;
    return $authService->checkBitrix24Auth();
}

/**
 * Редирект на страницу ошибки доступа
 * 
 * Обертка для обратной совместимости
 */
function redirectToFailure()
{
    global $authService;
    $authService->redirectToFailure();
}
