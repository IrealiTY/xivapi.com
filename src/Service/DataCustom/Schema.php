<?php

namespace App\Service\DataCustom;

use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\ManualHelper;

class Schema extends ManualHelper
{
    use ArrayHelper;
    
    // always last
    const PRIORITY = 9999;
    
    public function handle()
    {
        $content = (array)$this->redis->get('content');
        $this->io->progressStart(count($content));
        
        foreach ($content as $contentName) {
            $this->io->progressAdvance();
            
            $schema = [
                'count'  => 0,
                'data'   => null,
            ];
    
            $ids = $this->redis->get("ids_{$contentName}");
            
            if (!$ids) {
                continue;
            }
            
            foreach ($ids as $id) {
                $content = $this->redis->get("xiv_{$contentName}_{$id}");
    
                // count total fields
                $schemaObject       = json_decode(json_encode($content), true);
                $schemaCount        = count($schemaObject, COUNT_RECURSIVE);
                
                // if above max, process it
                if ($schemaCount > $schema['count']) {
                    // build schema and columns
                    $contentSchema  = $this->describeArray($schemaObject);
                    $contentColumns = array_keys($this->flattenArray($contentSchema));
                    
                    $schema = [
                        'count'  => $schemaCount,
                        'data'   => [
                            'ContentID'      => $id,
                            'ContentSchema'  => $contentSchema,
                            'ColumnCount'    => $schemaCount,
                            'Columns'        => $contentColumns
                        ],
                    ];
                }
            }
    
            // save
            $this->redis->set("schema_{$contentName}", $schema['data'], self::REDIS_DURATION);
        }
    
        $this->io->progressFinish();
        $this->io->text('Schemas built for all content');
    }
}
