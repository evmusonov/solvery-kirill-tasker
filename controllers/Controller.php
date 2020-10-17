<?php

namespace app\controllers;

use app\components\Jwt;
use app\components\Request;
use app\components\Response;

abstract class Controller
{
    /**
     * Ассоциативный массив данных полученных с клиента  (GET, POST, php://input)
     * @var array
     */
    protected $requestData;

    /**
     * Объект токена с идентификатором пользователя
     * @var object
     */
    protected $requestToken;

    /**
     * Массив с файлами прлученных из $_FILES либо пустой массив если файлы не передавались
     * @var array
     */
    protected $requestFiles;

    /**
     * Имя HTTP-метода с помощью которого был отправлен запрос
     * @var string
     */
    protected $method;

    /**
     * Масиив, с именами методов запроса которые должны обрабатываться только после успешной идентификации токена
     * 
     * - По умолчанию массив пустой, но может переопределяться в дочерних контроллерах
     * - Используется в момент проверки запроса на возможность запуска метода обработки этого запроса
     * @var array
     */
    protected $securedByToken = [];

    /**
     * Заполняет свойства объекта данными из запроса и проверяет возможность обработки данного запроса при отсуствии токена
     * 
     * - Строка любого запрос обрабатывается в методе parse класса Router, где устанавливается контроллер и метод которые должны обработать запрос, однако обработка будет разрешена только если описываемый метод вернёт true а вернёт он истину только в том случае если:
     *  -- Токен для этого запроса не нужен - нужен или нет токен определяется наличем имени метода в массиве хранящимся в свойстве $this->securedByToken - для каждого дочернего контроллера могут быть указаны свои обязательные методы, это надо смотреть в конкретном контроллере
     *  -- токен для этого запроса нужен и токен есть
     * 
     * @return bool|string при успешном выполнении возвращается true в противном случае возвращается Response::error('Wrong auth token', 401)
     */
    public function beforeInit()
    {
        $this->requestData = Request::getDataFromInput();
        //$this->requestFiles = Request::getFiles();
        //$this->requestToken = Jwt::get();
        //$this->method = Request::getHttpMethod();
        
        if (is_null($this->requestToken) && in_array(strtolower($this->method), $this->securedByToken)) {
            return Response::error('Wrong auth token', 401);
        }

        return true;
    }

    
}