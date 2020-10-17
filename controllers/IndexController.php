<?php

namespace app\controllers;

use app\components\Render;
use app\models\DB;
use app\models\Project;

class IndexController extends Controller
{
    public function index()
    {
        return Render::view('index', [
            'projects' => Project::get()
        ]);
    }

    public function edit()
    {

    }
}