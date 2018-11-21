<?php

namespace App\Service\LodestoneQueue;

use Ramsey\Uuid\Uuid;

class CharacterQueue
{
    /** @var RabbitMQ */
    private $rabbit;

    public function __construct(RabbitMQ $rabbit)
    {
        $this->rabbit = $rabbit;
    }

    /**
     * Queue a character to be parsed, doesn't matter if it's add, update or whatever
     *
     * @param $ids - a single id or an array of ids
     * @param bool $priority - If set true then it will be on the _fast queue which is used for REST requests
     */
    public function queue($ids, $priority = false)
    {
        $ids = is_string($ids) ? [ $ids ] : $ids;

        $this->rabbit->connect('characters'. ($priority ? '_fast' : '_auto'));

        foreach ($ids as $id) {
            $this->rabbit->sendMessage([
                'request_id' => Uuid::uuid4()->toString(),
                'action'     => LodestoneApi::GET_CHARACTER,
                'id'         => $id
            ]);
        }

        $this->rabbit->close();
    }
}
