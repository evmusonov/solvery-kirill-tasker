<?php

namespace app\models;

class Project
{
    public static function get()
    {
        $query = DB::getPDO()->query("select * from projects");

        return $query->fetchAll();
    }
}