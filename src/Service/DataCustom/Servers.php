<?php

namespace App\Service\DataCustom;

use App\Service\Content\GameServers;
use App\Service\Helpers\ManualHelper;

class Servers extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $this->io->text(__METHOD__);
        $ids = $this->getContentIds('World');
    
        foreach ($ids as $id) {
            $key = "xiv_World_{$id}";
            $world = $this->redis->get($key);
    
            $world->InGame = in_array($world->Name_en, GameServers::LIST);
        
            // save
            $this->redis->set($key, $world, self::REDIS_DURATION);
}
}
}
