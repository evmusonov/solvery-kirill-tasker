<?php
namespace app\components;

class Render
{
    public static function view($view, $params = null)
    {
        $path = __DIR__ . "/../views/$view.php";

        if (file_exists($path)) {
            if (!is_null($params)) {
                foreach ($params as $key => $value) {
                    $$key = $value;
                }
            }
            require_once $path;
        }

        return null;
    }
}