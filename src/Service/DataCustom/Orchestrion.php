<?php

namespace App\Service\DataCustom;

use App\Service\Helpers\ManualHelper;

class Orchestrion extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $this->io->text(__METHOD__);
        $ids = $this->getContentIds('Orchestrion');
    
        foreach ($ids as $id) {
            $key = "xiv_Orchestrion_{$id}";
            
            // append OrchestrationUiParam
            $Orchestrion = $this->redis->get($key);
            $Orchestrion->OrchestrionUiparam = $this->redis->get("xiv_OrchestrionUiparam_{$id}");
            
            // save
            $this->redis->set($key, $Orchestrion, self::REDIS_DURATION);
        }
    }
}
