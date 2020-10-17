<?php

namespace app\controllers;

use app\components\Render;
use app\models\DB;

class ProjectsController extends Controller
{
    public function add()
    {
        if (isset($_POST['name']) && !empty($_POST['name'])) {
            $query = DB::getPDO()->query("select * from projects where name = '" . $_POST['name'] . "'");
            $result = $query->fetchAll();
            if (count($result)) {
                return Render::view('index', ['error' => 'Ошибка добавления']);
            }

            $query = DB::getPDO()->query("insert into projects (name) values ('" . $_POST['name'] . "')");
            if ($query) {
                header("Location: /");
                exit;
                //return Render::view('index');
            }
        }

        return Render::view('index', ['error' => 'Ошибка добавления']);
    }
}