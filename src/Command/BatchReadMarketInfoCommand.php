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

class BatchReadMarketInfoCommand extends Command
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
            ->setName('BatchReadMarketInfoCommand')
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
        $total = count($itemIds);

        foreach ($itemIds as $i => $id) {
            if ($id > $finish) {
                break;
            }
            
            if ($id < $start) {
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
            
            $current = ($i + 1);
            $this->io->text("[". date('H:i:s') ."] {$current}/{$total} GET {$id} - {$response->Payload->Item->Name_en} == {$response->Payload->Market->Lodestone->ID}");
        }
        
        $this->endClock();
    }
}
