<?php

namespace App\Command;

use App\Service\Redis\Cache;
use App\Service\Search\SearchContent;
use App\Service\SearchElastic\ElasticMapping;
use App\Service\SearchElastic\ElasticSearch;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSearchCommand extends Command
{
    use CommandHelperTrait;
    
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('UpdateSearchCommand')
            ->setDescription('Deploy all search data to live!')
            ->addArgument('environment',  InputArgument::REQUIRED, 'prod OR dev')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->setSymfonyStyle($input, $output)
            ->title('DEPLOY TO SEARCH')
            ->startClock();
    
        // connect to production cache
        [$ip, $port] = $input->getArgument('environment') == 'prod'
            ? explode(',', getenv('ELASTIC_SERVER_PROD'))
            : explode(',', getenv('ELASTIC_SERVER_LOCAL'));
        
        $elastic = new ElasticSearch($ip, $port);
        $cache   = new Cache();
        
        // import documents to ElasticSearch
        foreach (SearchContent::LIST as $contentName) {
            $index  = strtolower($contentName);
            $ids    = $cache->get("ids_{$contentName}");
            $total  = count($ids);
            $docs   = [];
        
            $this->io->text("<info>ElasticSearch import: {$total} {$contentName} documents to index: {$index}</info>");

            // rebuild index
            $elastic->deleteIndex($index);
    
            // dynamic mappings!
            $mapping = [
                'search' => [
                    '_source' => [ 'enabled' => true ],
                    'dynamic' => true,
                    'dynamic_templates' => [
                        [
                            'strings' => [
                                'match_mapping_type' => 'string',
                                'mapping' => ElasticMapping::STRING
                            ]
                        ]
                    ],
                ],
            ];
    
            // create index
            $elastic->addIndex($index, $mapping, []);
    
            // Add documents to elastic
            $count = 0;
            $this->io->progressStart($total);
            foreach ($ids as $id) {
                $count++;

                $content = $cache->get("xiv_{$contentName}_{$id}");
                $docs[] = $content;
                unset($content);
    
                // insert docs
                if ($count > 250) {
                    $this->io->progressAdvance($count);
                    $elastic->bulkDocuments($index, 'search', $docs);
                    $docs = [];
                    $count = 0;
                }
            }
    
            // add any reminders
            if ($count > 0) {
                $elastic->bulkDocuments($index, 'search', $docs);

            }
            $this->io->progressFinish();
        }
    
        unset($content, $docs);
        $this->complete()->endClock();
    }
}
