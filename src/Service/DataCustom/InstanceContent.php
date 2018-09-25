<?php

namespace App\Service\DataCustom;

use App\Service\Content\ContentMinified;
use App\Service\Helpers\ManualHelper;

class InstanceContent extends ManualHelper
{
    const PRIORITY = 20;
    
    private $contentFinderConditions = [];
    
    public function handle()
    {
        $this->io->text(__METHOD__);
        
        // Warm-Up
        foreach ($this->redis->get('ids_ContentFinderCondition') as $id) {
            $this->contentFinderConditions[$id] = ContentMinified::mini(
                $this->redis->get("xiv_ContentFinderCondition_{$id}")
            );
        }
        
        $ids = $this->getContentIds('InstanceContent');
        foreach ($ids as $id) {
            $key = "xiv_InstanceContent_{$id}";
            $instanceContent = $this->redis->get($key);
            
            // set fields
            $instanceContent->ContentFinderCondition = null;
            $instanceContent->ContentMemberType = null;
            $instanceContent->ContentType = null;
            $instanceContent->Icon = null;
            $instanceContent->Banner = null;
            
            $this->addContentFinderCondition($instanceContent);
            $this->addInstanceBosses($instanceContent);
            
            #$this->setCombinedCurrency($instanceContent);
            #$this->setAdditionalData($instanceContent);
    
            // save
            $this->redis->set($key, $instanceContent, self::REDIS_DURATION);
        }
    }
    
    /**
     * Add content finder condition data
     */
    private function addContentFinderCondition($instanceContent)
    {
        // Content finder condition
        foreach ($this->contentFinderConditions as $condition) {
            if ($condition->InstanceContent === $instanceContent->ID) {
                $instanceContent->ContentFinderCondition = $condition;
                break;
            }
        }
        
        if (!$instanceContent->ContentFinderCondition) {
            return;
        }
        
        // Descriptions
        $descriptions = $this->redis->get("xiv_ContentFinderConditionTransient_{$instanceContent->ContentFinderCondition->ID}");
        $instanceContent->Description_en = $descriptions->Description_en;
        $instanceContent->Description_ja = $descriptions->Description_ja;
        $instanceContent->Description_de = $descriptions->Description_de;
        $instanceContent->Description_fr = $descriptions->Description_fr;
        
        // Content Member Type
        $instanceContent->ContentMemberType = $this->redis->get("xiv_ContentMemberType_{$instanceContent->ContentFinderCondition->ContentMemberType}");
        
        // ContentType
        $instanceContent->ContentType = $this->redis->get("xiv_ContentType_{$instanceContent->ContentFinderCondition->ContentType}");
        $instanceContent->Icon = $instanceContent->ContentType->Icon;
        $instanceContent->Banner = $instanceContent->ContentFinderCondition->Icon;
    }
    
    /**
     * Add instance bosses
     */
    private function addInstanceBosses($instanceContent)
    {
        // Main boss
        if (isset($instanceContent->BNpcBaseBoss->ID)) {
            $instanceContent->BNpcBaseBoss->BNpcName = $this->redis->get("xiv_BNpcName_{$instanceContent->BNpcBaseBoss->ID}");
        }
    }
}
