<?php

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // Этот код ошибки не включен в error_reporting,
        // так что пусть обрабатываются стандартным обработчиком ошибок PHP
        return false;
    }

    http_response_code(500);
    header('Content-type: text/html');
    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>Пользовательская ОШИБКА</b> [$errno] $errstr\n";
            echo "  Фатальная ошибка в строке $errline файла $errfile";
            echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
            echo "Завершение работы...<br>\n";
            exit(1);
            break;

        case E_USER_WARNING:
            echo "<b>Пользовательское ПРЕДУПРЕЖДЕНИЕ</b> [$errno] $errstr\n";
            break;

        case E_USER_NOTICE:
            echo "<b>Пользовательское УВЕДОМЛЕНИЕ</b> [$errno] $errstr\n";
            break;

        default:
            echo "Неизвестная ошибка: [$errno] $errstr\n";
            echo "В строке $errline файла $errfile";
            break;
    }

    /* Не запускаем внутренний обработчик ошибок PHP */
    return true;
}