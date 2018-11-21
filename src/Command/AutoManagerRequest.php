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
 * | This would run on the SYNC side. SYNC processes requests.
 * |
 * |    * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoManagerRequest characters_fast
 * |
 * |    php bin/console AutoManagerQueue
 * |
 */
class AutoManagerRequest extends Command
{
    protected function configure()
    {
        $this
            ->setName('AutoManagerRequest')
            ->setDescription("Auto manage lodestone population queues.")
            ->addArgument('queue', InputArgument::REQUIRED, 'Name of RabbitMQ queue.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new Manager(new SymfonyStyle($input, $output));
        $manager->processRequests($input->getArgument('queue'));
    }
}
