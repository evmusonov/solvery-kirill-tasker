<?php

namespace app\components;

/**
 *  FilesHandler - формирование из массива $_SERVER необработанной строки до предпологаемого файла и возврат этой строки
 *  
 * - определяет содержит ли запрос браузера путь до статического файла, или же в запросе указан контроллер и его метод. Применяется в index.php 
 */
class FilesHandler
{
    /**
     * Путь до предполагаемого  файла либо null
     * 
     * - по умолчанию null 
     * - Хранит необработанную строку состоящую из DOCUMENT_ROOT '/backend' REQUEST_URI
     * - Значение присваивается или не присваивается в self::isFile()
     * @var string||null $filePath 
     */
    private static $filePath;

    /**
	 * Название директории на наличие которой проверяется строка запроса в self::isFile() 
     * 
     * @var string $urlKey 
     *
     */
    private static $urlKey = '/sources';

    /**
     *  Формирует строку до предполагаемого файла
     * 
     *  - если в REQUEST_URI нет строки хранящейся в self::$urlKey формирует необработанную строку до файла,  сохраняет её в self::$filePath и возвращает true
     *  - если в REQUEST_URI есть строка хранящаяся в self::$urlKey возвращается false

     *  @return Bool 
     */
    public static function isFile()
    {
        if (mb_strpos($_SERVER['REQUEST_URI'], self::$urlKey) !== false) {
            self::$filePath = $_SERVER['DOCUMENT_ROOT'] . '/backend' . $_SERVER['REQUEST_URI'];
            return true;
        }

        return false;
    }

    /**
     *  Возврат содержимого файла по пути из self::$filePath если путь корректен
     *  
     *  - если в self::$filePath есть строка и эта строка является путём до существующего файла то возвращается содержимое этого файла
     *  -	иначе возвращаем 404
     *  @return String
     */
    public static function getFile()
    {
        if (file_exists(self::$filePath)) {
            return file_get_contents(self::$filePath);
        }

        return Response::error('File not found', 404);
    }

    /**
     *  Возвращает MIME-тип содержимого файла, путь до которого хранится в self::$filePath или FALSE в случае возникновения ошибки. 
     *  
     *  @return String||Bool
     */
    public static function getType()
    {
        return mime_content_type(self::$filePath);
    }
}