<?php

namespace App\Command;

use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CompanionAppLoginCommand extends Command
{
    use CommandHelperTrait;
    
    protected function configure()
    {
        $this
            ->setName('CompanionAppLoginCommand')
            ->setDescription('Re-login to each character')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new CompanionTokenManager();
        $manager->setSymfonyStyle(
            new SymfonyStyle($input, $output)
        );
        
        $manager->go();
    }
}
