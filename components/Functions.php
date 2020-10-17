<?php

namespace app\components;

/**
 *  Класс компонент где статичными методами являются функции не относящиеся не к одному другому классу
 * 
 */
class Functions
{
    /**
     * Replace CamelCased text by text with spaces.
     * 
     * - принимает строку преобразует в нижний регистр с нижним подчёркиванием вместо пробела и возвращает её
     * - используется преимущественно для получения имён таблиц
     * @param string $text Text to replace.
     *
     * @return string
     */
    public static function splitByCapital(string $text) : string
    {
        $pattern = '/((?<=\p{Ll})\p{Lu}|\p{Lu}(?=\p{Ll}))/u';
        $result = preg_replace($pattern, '_$1', $text);

        return trim($result);
    }

    /**
     * Формирует и возвращает строку в верблюжьей нотации все слова слитно каждое слово с большой буквы
     * @param string $str
     * @return string
     */
    public static function toCamelCase(string $str) : string
    {
        $str = str_replace(' ', '', ucwords(str_replace('-', ' ', $str)));
        $str[0] = strtoupper($str[0]);

        return $str;
    }

    /**
     * Возвращает все заголовки, содержимое переданного заголовка либо null
     * 
     * Если параметр не передаётся то возвращаются все заголовки, если передан параметр то возвращается значение заголовка имя которого передано в параметре либо null если данного заголовка не существует
     * 
     * @param string $name - им заголовка содержимое которого необходимо получить
     * @return array|string|null
     */
    public static function headers(string $name = '')
    {
        $headers = getallheaders();
        if (empty($name)) {
            return $headers;
        }

        if (isset($headers[$name])) {
            return $headers[$name];
        }

        return null;
    }

    /**
     *  принимает строку имени класса с учётом пространства имён разбирает её получает последний элемент пути  преобразует в нижний регистр с нижним подчёркиванием вместо пробела (если есть) и возвращает её
     *  - 
     *  @param string $className
     *  @return string
     */
    public static function getTableName(string $className)
    {
        $className = explode("\\", $className);
        $model = end($className);
        $name = Functions::splitByCapital(lcfirst($model));
        return strtolower($name);
    }

    public static function getDomainWithProtocol() : string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? "https://" : "http://");
        return $protocol . $_SERVER["SERVER_NAME"];
    }

    public static function getUid($string)
    {
        $all = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $count = strlen($all) - 1;
        $char = $all[rand(0, $count)];
        $salt = time();

        return $char . md5($string . $salt);
    }

    /**
     *  Получает и возвращает ключ последнего элемента переданного массива
     *
     *  @param array $array Массив индексный или ассоциативный
     *  @return string|number имя ключа или индекс элемента массива
     */
    public static function arrayKeyLast(array $array)
    {
        $keys = array_keys($array);
        return $keys[count($keys) - 1];
    }
}