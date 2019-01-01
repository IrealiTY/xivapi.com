<?php

namespace App\Command;

use App\Service\Data\FileReader;
use App\Service\SearchElastic\ElasticSearch;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSearchLoreCommand extends Command
{
    use CommandHelperTrait;
    
    protected function configure()
    {
        $this
            ->setName('UpdateSearchLoreCommand')
            ->setDescription('Deploy all search data to live!')
            ->addArgument('environment',  InputArgument::OPTIONAL, 'prod OR dev')
            ->addArgument('content', InputArgument::OPTIONAL, 'Run a specific content')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->setSymfonyStyle($input, $output)
            ->title('LORE FINDER')
            ->startClock();

        // connect to production cache
        [$ip, $port] = (in_array($input->getArgument('environment'), ['prod','staging']))
            ? explode(',', getenv('ELASTIC_SERVER_PROD'))
            : explode(',', getenv('ELASTIC_SERVER_LOCAL'));
        
        $elastic = new ElasticSearch($ip, $port);
    
        /**
         * Lore finder:
         * - List out methods to call, eg:
         *      addQuestDialogue
         *      addItemDescriptions
         *      addBaloons
         *
         * Each function should build the data and describe its format
         * Each function adds the data to elastic under the prefix "lore_XXXX"
         *
         * Each function should have a "Text" (eg name, description, etc) and a Combined Text which
         * includes all languages.
         *
         * Try include KR and CN. Data can be provided.
         */
        
        // todo - work on this
        FileReader::parseCsvFile('< filename >');
        
    }
}
