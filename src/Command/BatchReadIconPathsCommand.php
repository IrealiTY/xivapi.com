<?php

namespace App\Command;

use App\Service\Companion\CompanionMarket;
use App\Service\Companion\CompanionResponse;
use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BatchReadIconPathsCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionMarket */
    private $market;
    /** @var Cache */
    private $cache;
    
    public function __construct(
        ?string $name = null,
        EntityManagerInterface $em,
        CompanionMarket $market,
        Cache $cache
    ) {
        $this->em = $em;
        $this->market = $market;
        $this->cache = $cache;
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('BatchReadIconPathsCommand')
            ->setDescription('Do something really stupid')
            ->addArgument('start', InputArgument::OPTIONAL, 'Starting ID')
            ->addArgument('finish', InputArgument::OPTIONAL, 'Ending ID')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->text('Starting ...');
        $this->startClock();
        
        $start = $input->getArgument('start') ?: 0;
        $finish = $input->getArgument('finish') ?: 100000;
        
        $itemIds = $this->cache->get('ids_Item');
        $filename = __DIR__.'/resources/market_items.txt';
        $total = count($itemIds);
        
        $existing = [];
        foreach (array_filter(explode("\n", file_get_contents($filename))) as $i => $item) {
            $id = explode("|", $item)[0];
            $existing[trim($id)] = 1;
        }
        
        foreach ($itemIds as $i => $id) {
            if ($id > $finish) {
                break;
            }
            
            if ($id < $start) {
                continue;
            }

            if (array_key_exists($id, $existing)) {
                continue;
            }
            
            /** @var CompanionResponse $response */
            try {
                $response = $this->market->getItemMarketData($id);
                $response = $response->response;
            } catch (\Exception $ex) {
                $this->io->error($ex->getMessage());
                continue;
            }
            
            $string = sprintf(
                "%s|%s|%s|%s|%s|%s\n",
                $id,
                $response->Payload->Market->Lodestone->ID,
                $response->Payload->Market->Lodestone->Icon,
                $response->Payload->Market->Lodestone->IconHq,
                time(),
                $response->Payload->Item->Name_en
            );

            file_put_contents($filename, $string, FILE_APPEND);
            
            $current = ($i + 1);
            $this->io->text("[". date('H:i:s') ."] {$current}/{$total} GET {$id} - {$response->Payload->Item->Name_en} == {$response->Payload->Market->Lodestone->ID}");
        }
        
        $this->endClock();
    }
}
