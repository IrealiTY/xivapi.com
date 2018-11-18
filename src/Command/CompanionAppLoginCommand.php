<?php

namespace App\Command;

use App\Service\Companion\CompanionTokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument('account', InputArgument::REQUIRED, 'Which account to login to, A or B')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new CompanionTokenManager();
        $manager->setSymfonyStyle(
            new SymfonyStyle($input, $output)
        );
        
        if ($input->getArgument('account') === 'debug') {
            $manager->go('COMPANION_APP_ACCOUNT_A', true);
            return;
        }
        
        $accounts = [
            'A' => 'COMPANION_APP_ACCOUNT_A',
            'B' => 'COMPANION_APP_ACCOUNT_B'
        ];

        // grab account and process logins, go, go, go!
        $account = $accounts[$input->getArgument('account')];
        $manager->go($account);
    }
}
