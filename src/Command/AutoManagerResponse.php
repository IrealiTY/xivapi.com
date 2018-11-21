<?php

namespace App\Command;

use App\Service\LodestoneQueue\Manager;
use App\Service\Redis\Cache;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneAutoManagers\{
    AutoCharacterManager,
    AutoFreeCompanyManager,
    AutoLinkshellManager,
    AutoPvpTeamManager
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * | This would run on the XIVAPI side. XIVAPI processes responses.
 * |
 * |    * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoManagerResponse characters_fast
 * |
 * |    php bin/console AutoManagerResponse
 * |
 */
class AutoManagerResponse extends Command
{
    protected function configure()
    {
        $this
            ->setName('AutoManagerResponse')
            ->setDescription("Auto manage lodestone population queues.")
            ->addArgument('queue', InputArgument::REQUIRED, 'Name of RabbitMQ queue.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new Manager(new SymfonyStyle($input, $output));
        $manager->processResponse($input->getArgument('queue'));
    }
}
