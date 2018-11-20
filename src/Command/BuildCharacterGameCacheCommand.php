<?php

namespace App\Command;

use App\Service\Content\LodestoneData;
use App\Service\Content\Hash;
use App\Service\Redis\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This should run every day or week
 *      0 0 * * 0 /usr/bin/php /home/dalamud/dalamud/bin/console BuildCharacterGameCacheCommand
 */
class BuildCharacterGameCacheCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var Cache $cache */
    private $cache;
    /** @var array */
    private $data;
    
    public function __construct(?string $name = null, Cache $cache)
    {
        parent::__construct($name);
        $this->cache = $cache;
    }
    
    protected function configure()
    {
        $this
            ->setName('BuildCharacterGameCacheCommand')
            ->setDescription('Cache game data for characters')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title('Patch Management');

        $this->cacheItems();
        $this->CacheMounts();
        $this->cacheGeneric('Companion');
        $this->cacheGeneric('Race');
        $this->cacheGeneric('Tribe');
        $this->cacheGeneric('Title');
        $this->cacheGeneric('GrandCompany');
        $this->cacheGeneric('GuardianDeity');
        $this->cacheGeneric('Town');
        $this->cacheGeneric('BaseParam');
        $this->cacheGeneric('GCRankGridaniaFemaleText');
        $this->cacheGeneric('GCRankGridaniaMaleText');
        $this->cacheGeneric('GCRankLimsaFemaleText');
        $this->cacheGeneric('GCRankLimsaMaleText');
        $this->cacheGeneric('GCRankUldahFemaleText');
        $this->cacheGeneric('GCRankUldahMaleText');

        // cache for 100 days!
        file_put_contents(__DIR__.'/resources/lodestone_data.json', json_encode($this->data, JSON_PRETTY_PRINT));
        $this->cache->set(LodestoneData::CACHE_KEY, $this->data, (60*60*24*100));
        $this->complete();
    }
    
    private function cacheGeneric($contentName)
    {
        $this->io->text("Cache: {$contentName}");
        foreach ($this->cache->get("ids_{$contentName}") as $id) {
            $content = $this->cache->get("xiv_{$contentName}_{$id}");
            $this->data[$contentName][Hash::hash($content->Name_en)] = $content->ID;
            
            if (isset($content->NameFemale_en)) {
                $this->data[$contentName][Hash::hash($content->NameFemale_en)] = $content->ID;
            }
        }
    }

    private function CacheMounts()
    {
        $this->io->text("Cache: Mount");
        foreach ($this->cache->get("ids_Mount") as $id) {
            $content = $this->cache->get("xiv_Mount_{$id}");

            if ($content->Order == -1) {
                continue;
            }

            $this->data['Mount'][Hash::hash($content->Name_en)] = $content->ID;
        }
    }
    
    private function cacheItems()
    {
        $this->io->text('Cache: Item');
        foreach ($this->cache->get('ids_Item') as $id) {
            $item = $this->cache->get("xiv_Item_{$id}");
        
            // don't care about these
            if (empty($item->ItemUICategory->ID)) {
                continue;
            }
        
            // soul Stones
            if ($item->ItemUICategory->ID == 62) {
                $this->data['Item'][Hash::hash($item->Name_en)] = $item->ID;
                continue;
            }
        
            // materia
            if ($item->ItemUICategory->ID == 58) {
                $this->data['Item'][Hash::hash($item->Name_en)] = $item->ID;
                continue;
            }
        
            // dyes
            if ($item->ItemUICategory->ID == 55) {
                $this->data['Item'][Hash::hash($item->Name_en)] = $item->ID;
                continue;
            }
        
            // all equipment gear can be repaired
            if (!empty($item->ClassJobRepair)) {
                $this->data['Item'][Hash::hash($item->Name_en)] = $item->ID;
                continue;
            }
        }
    }
}
