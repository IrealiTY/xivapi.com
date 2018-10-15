<?php

namespace App\Command;

use App\Service\Apps\AppManager;
use App\Service\Redis\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#
#  */5 * * * * /usr/bin/php /home/dalamud/dalamud/bin/console ClearDevAppStatisticsCommand
#
class ClearDevAppStatisticsCommand extends Command
{
    /** @var Cache */
    private $cache;
    
    public function __construct(
        ?string $name = null,
        Cache $cache
    ) {
        $this->cache = $cache;

        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('ClearDevAppStatisticsCommand')
            ->setDescription('Clear Rate Limits')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        //
        // Delete all 2 minute old rate limit tracking
        //
        $io->text('Clearing rate limits');
        $deleteAfter = time() - 120;
        foreach ($this->cache->keys('app_rate_limit_*') as $key) {
            [$a, $b, $c, $app, $time] = explode('_', $key);
            
            if ($time < $deleteAfter) {
                $this->cache->delete($key);
            }
        }
        
        //
        // Delete all old history
        //
        $io->text('Clearing app history');
        $deleteAfter = time() - (AppManager::MAX_HISTORY_TIME * 60);
        foreach ($this->cache->keys('app_hits_history_*') as $key) {
            $history = $this->cache->get($key);
            $history = json_decode(json_encode($history), true);
            
            foreach ($history as $i => $h) {
                if ($h[0] < $deleteAfter) {
                    unset($history[$i]);
                }
            }
    
            $history = array_values(array_filter($history));
            $this->cache->set($key, $history, AppManager::CACHE_HISTORY_TIME);
        }

        $io->text('Completed');
    }
}
