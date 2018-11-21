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
 * |
 * |    * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoManagerQueue
 * |
 * | DEV:
 * |    php bin/console AutoManagerQueue
 * |
 */
class AutoManagerQueue extends Command
{
    protected function configure()
    {
        $this
            ->setName('AutoManagerQueue')
            ->setDescription("Auto manage lodestone population queues.")
            ->addArgument('direction', InputArgument::REQUIRED, 'Incoming or Outgoing queue');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new Manager(new SymfonyStyle($input, $output));

        $input->getArgument('direction') === 'incoming'
            ? $manager->incoming()
            : $manager->outgoing();
    }
}
