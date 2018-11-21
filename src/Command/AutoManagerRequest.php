<?php

namespace App\Command;

use App\Service\LodestoneQueue\Manager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * | This would run on the SYNC side. SYNC processes requests.
 * |
 * |    * * * * * /usr/bin/php /home/dalamud/xivapi.com/bin/console AutoManagerRequest characters_fast
 * |
 * |    php bin/console AutoManagerRequest characters_fast
 * |
 */
class AutoManagerRequest extends Command
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);
        $this->em = $em;
    }
    
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
        $manager = new Manager(new SymfonyStyle($input, $output), $this->em);
        $manager->processRequests($input->getArgument('queue'));
    }
}
