<?php

namespace App\Command;

use App\Service\Redis\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLargeItemIconCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('GenerateLargeItemIconCommand')
            ->setDescription('Downloads large icons from SE for items using the Companion API')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api = 'https://xivapi.com/market/phoenix/items/%s';
        $url = 'https://img.finalfantasyxiv.com/lds/pc/global/images/itemicon/%s.png?%s';

        // redis cache
        $cache = new Cache();

        // loop through items
        $ids   = $cache->get('ids_Item');
        $total = count($ids);
        $count = 0;
        foreach ($ids as $itemId) {
            $count++;

            // grab market info as it includes item id
            // ... yes im a lazy shit; querying my own api
            $market = json_decode(sprintf($api, $itemId));

            // download if an icon exists
            if (!empty($market->Lodestone->Icon)) {
                // download icon and move it to local copy
                $iconUrl = sprintf($url, $market->Lodestone->LodestoneId, time());

                // local filename
                $filename = __DIR__ ."/../../public/i2/{$itemId}.png";

                // download icon
                copy($iconUrl, $filename);
            }

            // set secondary information
            $secondary = (Object)[
                'Icon2x'          => $filename ?? null,
                'LodestoneID'     => $market->Lodestone->LodestoneId,
                'LodestoneIcon'   => $market->Lodestone->Icon,
                'LodestoneIconHQ' => $market->Lodestone->IconHq,
            ];

            $cache->set("xiv2_Item_{$itemId}", $secondary);
            $this->io->text("{$count}/{$total}");
        }

    }
}
