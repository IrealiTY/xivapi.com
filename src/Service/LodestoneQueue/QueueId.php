<?php

namespace App\Service\LodestoneQueue;

use Ramsey\Uuid\Uuid;

class QueueId
{
    private static $id;

    public static function set()
    {
        self::$id = Uuid::uuid4()->toString() . '_' . date('Y_m_d_H_i');
    }

    public static function get()
    {
        return self::$id;
    }
}
