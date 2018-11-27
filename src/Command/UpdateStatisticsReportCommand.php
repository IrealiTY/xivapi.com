<?php

namespace App\Command;

use App\Service\Common\Statistics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStatisticsReportCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this->setName('UpdateStatisticsReportCommand');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title(__CLASS__);
        
        // clean the report
        $this->io->text('Cleaning report');
        Statistics::clean();

        // generate the report
        $this->io->text('Building report');
        Statistics::buildReport();
        $this->io->text('Finished');
    }
}
