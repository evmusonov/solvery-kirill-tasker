<?php

namespace app\controllers;

use app\components\Render;

class TasksController extends Controller
{
    public function show()
    {
        return Render::view('show');
    }
}