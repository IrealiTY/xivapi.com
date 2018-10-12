<?php

namespace App\Service\Lodestone;

use App\Service\Redis\Cache;

/**
 * Enrich character data with more details information rather
 * than just a series of IDs
 */
class CharacterDataEnrich
{
    /** @var Cache */
    private $cache;
    /** @var \stdClass */
    private $character;

    public function __construct()
    {
        $this->cache = new Cache();
    }

    public function enrich(\stdClass $character)
    {
        $this->character = $character;
    }
}
