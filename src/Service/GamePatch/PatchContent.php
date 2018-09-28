<?php

namespace App\Service\GamePatch;

use App\Service\Helpers\ManualHelper;

/**
 * Tracks patch info for each piece of content
 */
class PatchContent extends ManualHelper
{
    const FILENAME = __DIR__ .'/content/%s.json';
    
    const TRACKED_CONTENT = [
        'Achievement',
        'Action',
        'Addon',
        'Balloon',
        'ClassJob',
        'Companion',
        'CompanyAction',
        'CraftAction',
        'Emote',
        'Fate',
        'InstanceContent',
        'Item',
        'Leve',
        'Mount',
        'ENpcResident',
        'BNpcName',
        'Orchestrion',
        'Pet',
        'PlaceName',
        'Quest',
        'Recipe',
        'SpecialShop',
        'Status',
        'Title',
        'Trait',
        'Weather',
    ];

    public function handle()
    {
        $this->updatePatchPersistence();
        $this->updatePatchContent();
    }
    
    private function updatePatchContent()
    {
        $this->io->section('Updating tracked content');
        
        $patchService = new Patch();
    
        $total = count(self::TRACKED_CONTENT);
        foreach (self::TRACKED_CONTENT as $i => $contentName) {
            $current = ($i+1);
            $this->io->text("{$current}/{$total} <comment>Tracked: {$contentName}</comment>");
            $ids = $this->redis->get("ids_{$contentName}");
    
            // load patch file
            $json = file_get_contents(sprintf(self::FILENAME, $contentName));
            $json = json_decode($json, true);
            
            // process all content ids
            foreach ($ids as $contentId) {
                // grab the patchId for this contentId
                $patchId = $json[$contentId] ?? false;
                $patchId = $patchId == 1 ? 2 : $patchId;
    
                // grab content
                $key     = "xiv_{$contentName}_{$contentId}";
                $content = $this->redis->get($key);

                // set patch
                $content->GamePatch = $patchId ? $patchService->getPatchAtID($patchId) : null;
                
                // re-save content
                $this->redis->set($key, $content, self::REDIS_DURATION);
            }
        }
        
        $this->io->text(['Completed tracked-content patch versions', '', '']);
    }
    
    /**
     * @throws \Exception
     */
    private function updatePatchPersistence()
    {
        $this->io->section('Updating persistent patch data');
        
        // latest patch
        $patch = (new Patch())->getLatest();
        
        // loop through all tracked content
        $content = (array)$this->redis->get('content');
        $total   = count($content);
        $current = 0;
        foreach ($content as $contentName) {
            $current++;
            $this->io->text("{$current}/{$total} <comment>{$contentName}</comment>");
            $filename = sprintf(self::FILENAME, $contentName);
        
            // grab all content ids
            $ids = $this->redis->get("ids_{$contentName}");
        
            // no ids? skip
            if (!$ids) {
                continue;
            }
            
            // grab previous patch values if they exist, otherwise start a new list
            $list = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
        
            // loop through all content ids
            foreach ($ids as $id) {
                // grab content
                $content = $this->redis->get("xiv_{$contentName}_{$id}");
            
                // we only care about stuff with "name_en", skip entire file if no Name_en field
                if (!isset($content->Name_en)) {
                    break;
                }
            
                // we only care about stuff without a blank name_en
                if (strlen(trim($content->Name_en)) < 2) {
                    continue;
                }
            
                // save previous patch if it exists, otherwise use new patch id
                $list[$id] = $list[$id] ?? $patch->ID;
            }
        
            // save
            file_put_contents($filename, json_encode($list));
        }
    
        $this->io->text('Complete');
    }
}
