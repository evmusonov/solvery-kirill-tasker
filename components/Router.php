<?php

namespace app\components;

use app\controllers\Controller;
use app\controllers\IndexController;

/**
*  Получает строку запроса обрабатывает для получения контроллера метода и последующего их вызова с учётом параметров запроса
*  
*/
class Router
{
    public static function parse()
    {
        $uri = explode("?", $_SERVER['REQUEST_URI'])[0]; // Разбираем строку запроса

        if ($uri === '/') {
            return (new IndexController())->index();
        } else {
            $uri = mb_substr($uri, 1); // Убираем первый слеш для удобства
            $splitted = explode("/", $uri);

            if (count($splitted) == 1) { # /user
                $controller = self::getControllerNamespace(Functions::toCamelCase($splitted[0]));

                if (!method_exists($controller, 'index')) {
                    throw new \Exception('Метод не найден');
                }

                return $controller->index(); # user -> User, user-show -> UserShow
            } else {
                $controller = self::getControllerNamespace(Functions::toCamelCase($splitted[0]));
                $controller = new $controller;
                $action = $splitted[1];

                if (mb_strpos($action, '-') !== false) {
                    $action = explode("-", $action);
                    for ($i = 1; $i < count($action); $i++) {
                        $action[$i] = ucfirst($action[$i]);
                    }
                    $action = implode("", $action);
                }

                if (!method_exists($controller, $action)) {
                    throw new \Exception('Метод не найден');
                }

                return $controller->$action();
            }
        }
    }

    private static function getControllerNamespace($path)
    {
        return 'app\\controllers\\' . $path . 'Controller';
    }
}