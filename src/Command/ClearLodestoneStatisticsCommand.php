<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#
#  */5 * * * * /usr/bin/php /home/dalamud/dalamud/bin/console ClearLodestoneStatisticsCommand
#
class ClearLodestoneStatisticsCommand extends Command
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em, $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('ClearLodestoneStatisticsCommand')
            ->setDescription('Clear lodestone statistics over 1 day')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // delete logs after 1 day
        $time = time() - (60*60*24);
        $sql = "DELETE FROM lodestone_statistic WHERE added < {$time}";
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $output->writeln('Deleted lodestone statistics over 1 day');
    }
}
