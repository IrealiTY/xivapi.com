<?php

namespace App\Command;

use App\Entity\Character;
use App\Service\LodestoneQueue\CharacterQueue;
use App\Service\LodestoneQueue\Manager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * | This would run on the XIVAPI side. XIVAPI decides who to queue
 * |
 * |    * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoManagerQueue
 * |
 * |    php bin/console AutoManagerQueue
 * |
 */
class AutoManagerQueue extends Command
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
            ->setName('AutoManagerQueue')
            ->setDescription("Auto manage lodestone population queues.")
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        // queue characters to update
        $io->text('Queue: Characters to update');
        CharacterQueue::queue(
            $this->em->getRepository(Character::class)->findCharactersToUpdate(),
            CharacterQueue::AUTO
        );
    }
}
