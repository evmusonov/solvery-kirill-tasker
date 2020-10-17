<?php

namespace app\models;

use PDO;
use PDOException;

/**
 *  Изначальный класс осуществляющий подключение к базе данных в приложении 
 *  и возврата объекта подключения. 
 * 
 *  - Подключение осуществляется с помощью стандартного расширения PDO и PDOException (обработка ошибки PDO)
 *  - Применяется в родительском классе модели - BaseModel.php
 *  @see PDO https://www.php.net/manual/ru/class.pdo.php 
 *  @see PDOException https://www.php.net/manual/ru/class.pdoexception.php
 *  
 */
class DB
{
    /**
     *  хранит объект  подключения к базе данных (PDO), по умолчанию null
     * 
     *  - Объект PDO передаётся в свойство при вызове метода этого класса DB::getPDO()
     *  - Получить объект из свойства можно опять же вызовом метода DB::getPDO()
     *  @var Object|Null Объект подключения PDO или null
     */
    private static $instance = null;

    /**
     *  По умолчанию хранит пустую строку, но в свойство передаётся имя ключа от элемента в ассоциативном массиве где хранятся данные для подключения к базе данных, тоесть для подключения будет выбираться те данные, ключ от  которых хранится в данном свойстве.
     * 
     *  - Имя ключа передаётся в свойство в момент вызова статического метода DB::getPDO которому в качестве параметра как раз и передаётся строка в виде имени ключа
     *  - Свойство используется в методе данного класса DB::getPDO в момент подключения к базе данных для выбора параметров подключения 
     *  @var String имя ключа в ассоциативном массиве для выбора параметров подключения к базе данных
     */
    private static $connection = '';

    private function __construct() { /* ... @return Singleton */ }  // Защищаем от создания через new Singleton
    private function __clone() { /* ... @return Singleton */ }  // Защищаем от создания через клонирование
    private function __wakeup() { /* ... @return Singleton */ }  // Защищаем от создания через unserialize

    /**
     *  осуществляет подключение к базе данных, и возвращает  объект PDO
     * 
     *  - В качестве параметра в метод передаётся имя ключа в ассоциативном массиве определённом в /config/db.php 
     *  и хранящий опции подключения к базе данных, в результате чего можно осуществлять подключение под разными данными, по умолчанию ключ default
     *  - Объект подключения хранится в свойстве self::$instance т.е. в момент подключения метод проверяет есть ли в данном свойстве объект подключения если его там нет то осуществляется подключение в случае удачи объект сохраняется в данном свойстве
     *  - После подключения или если объект подключения уже есть из метода возвращается свойство self::$instance с объектом подключения
     *  
     *  @param String $connectionName имя ключа в ассоциативном массиве с данными для подключения к базе данных
     *  @return Object объект PDO
     */
    public static function getPDO($connectionName = 'default') {
        if (self::$instance === null || self::$connection != $connectionName) {
            self::$connection = $connectionName;
            try {
                $db = require __DIR__ . '/../config/db.php';
                self::$instance = new PDO($db[$connectionName]['dsn'] . ';charset=UTF8', $db[$connectionName]['username'], $db[$connectionName]['password']);
            } catch (PDOException $e) {
                echo print_r($db,1);
                echo 'Подключение не удалось: ' . $e->getMessage();
            }
        }

        return self::$instance;
    }
}