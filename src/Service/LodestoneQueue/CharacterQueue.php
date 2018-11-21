<?php

namespace App\Service\LodestoneQueue;

use Ramsey\Uuid\Uuid;

class CharacterQueue
{
    const AUTO  = 'characters_auto';
    const FAST  = 'characters_fast';

    /**
     * Add a character to the queue to be parsed (doesn't matter if it's to add or update)
     */
    public static function add($ids, string $queue)
    {
        $rabbit = new RabbitMQ();

        $ids = is_string($ids) ? [ $ids ] : $ids;

        $rabbit->connect($queue .'_request');

        foreach ($ids as $id) {
            $rabbit->sendMessage([
                'type'          => 'character',
                'queue'         => $queue,
                'requestId'     => Uuid::uuid4()->toString(),
                'method'        => LodestoneApi::GET_CHARACTER,
                'arguments'     => [ $id ],
            ]);
        }

        $rabbit->close();
    }
}
