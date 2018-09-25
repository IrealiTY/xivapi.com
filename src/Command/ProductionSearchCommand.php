<?php

namespace App\Command;

use App\Service\ElasticSearch\ElasticClient;
use App\Service\ElasticSearch\Mapping;
use App\Service\Redis\Cache;
use App\Service\SearchContent\Achievement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductionSearchCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var Cache */
    private $redis;
    /** @var ElasticClient */
    private $elastic;
    
    public function __construct()
    {
        $this->redis = new Cache();
        parent::__construct();
    }
    
    protected function configure()
    {
        $this
            ->setName('app:search')
            ->setDescription('Deploy all search data to live!')
            ->addArgument('environment', InputArgument::REQUIRED, 'Deploy production or dev?')
            ->addArgument('content_name', InputArgument::OPTIONAL, 'Run a specific content name')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->title('DEPLOY TO SEARCH');
    
        // connect to production redis
        [$ip, $port] = $input->getArgument('environment') == 'prod'
            ? explode(',', getenv('ELASTIC_SERVER_PROD'))
            : explode(',', getenv('ELASTIC_SERVER_LOCAL'));
        
        $this->io->text("Deploying to: {$ip}:{$port}");
        
        $this->elastic  = new ElasticClient($ip, $port);
        $filelist       = array_values(array_diff(scandir(__DIR__.'/../Service/SearchContent'), ['..', '.']));
        $total          = count($filelist);
        
        $searchStructure = (Object)[
            'indexes' => [],
            'views'   => [],
            'fields'  => [],
        ];
        
        foreach ($filelist as $i => $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
    
            $class = substr($file, 0, -4);
    
            // skip content_name
            if ($input->getArgument('content_name') && $input->getArgument('content_name') !== $class) {
                continue;
            }
    
            /** @var Achievement $class */
            $class = "\\App\\Service\\SearchContent\\{$class}";
            $class = new $class();
            $index = $this->deploy($class, [$total, ($i+1)]);
    
            $searchStructure->indexes[]      = $index;
            $searchStructure->views[$index]  = $class::FIELDS;
            $searchStructure->fields[$index] = $class::FIELDS;
        }
        
        // output indexes
        $searchDataFilename = __DIR__.'/../Service/Search/search_data.json';
        file_put_contents($searchDataFilename, json_encode($searchStructure));
        $this->io->text([
            "", "Search data saved to: {$searchDataFilename}", ""
        ]);

        $this->complete();
    }

    /**
     * Deploy search data for each piece of content
     */
    private function deploy($class, $progress)
    {
        $reflect            = new \ReflectionClass($class);
        $contentName        = $reflect->getShortName();
        $index              = strtolower($contentName);
        [$total, $current]  = $progress;
    
        $this->io->text("<fg=cyan>{$current}/{$total} - {$contentName}</>");
    
        // Get keys for this content piece
        $keys = $this->redis->keysList("xiv_{$contentName}_*");
    
        // Format data into the Search format
        foreach ($keys as $key => $info) {
            // Grab content and format the content into a Search format
            $content = $this->redis->get($key);
            $class->handle($content);
        }
    
        // Delete the table, this is so we can add the index with an updated schema
        // - This will delete all data in the table!
        if ($this->elastic->isIndex($index)) {
            $this->elastic->deleteIndex($index);
        }
        
        // index settings
        $settings = [
            'analysis' => Mapping::ANALYSIS
        ];
        
        // index mappings
        $mapping = [
            'search' => [
                '_source'    => [ 'enabled' => true ],
                'properties' => $class->schema,
            ],
        ];
    
        // Rebuild table with map that was generated
        $this->elastic->addIndex($index, $mapping, $settings);
    
        // Add content in bulks of 100
        $this->io->progressStart(count($class->documents));
        foreach (array_chunk($class->documents, 100, true) as $docs) {
            $this->io->progressAdvance(count($docs));
            $this->elastic->bulkDocuments($index, 'search', $docs);
        }
        $this->io->progressFinish();
        
        return $index;
    }
}
