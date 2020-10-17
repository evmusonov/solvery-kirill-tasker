<?php

namespace app\models;

class BlockedIp extends BaseModel
{
    private static $attemptAmount = 5;
    private static $expireTime = 60 * 60 * 24 * 14; // 2 weeks

    public static function isBlocked()
    {
        $result = DB::getPDO('users')->query("select * from blocked_ip where ip = '" . $_SERVER['REMOTE_ADDR'] . "' LIMIT 1");
        if ($result->rowCount()) {
            return true;
        }

        return false;
    }

    public static function checkForBlocking(): void
    {
        $twoMinutes = 120;
        $offset = time() - $twoMinutes;
        $result = DB::getPDO('users')->query("select * from log_user_enter where ip = '" . $_SERVER['REMOTE_ADDR'] . "' and `time` > " . $offset . " and result = 0 LIMIT 5");
        if (!is_null($result) && $result->rowCount() == self::$attemptAmount) {
            self::add();
        }
    }

    public static function add(): void
    {
        $block = new self;
        $block->ip = $_SERVER['REMOTE_ADDR'];
        $block->expire = time() + self::$expireTime;
        $block->save();
    }
}