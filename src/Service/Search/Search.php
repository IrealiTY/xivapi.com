<?php

namespace App\Service\Search;

use App\Service\ElasticSearch\ElasticClient;

class Search
{
    use TraitStringSearch;
    use TraitFilterSearch;
    
    /** @var ElasticClient */
    private $elasticClient;

    function __construct()
    {
        // connect to production redis
        [$ip, $port] = explode(',', getenv('ELASTIC_SERVER_LOCAL'));
        $this->elasticClient = new ElasticClient($ip, $port);
    }
    
    /**
     * @throws \Exception
     */
    public function handleRequest(SearchRequest $searchRequest, SearchResponse $searchResponse)
    {
        $this->elasticClient->QueryBuilder
            ->reset()
            ->sort($searchRequest->sortField, $searchRequest->sortOrder)
            ->limit($searchRequest->limitStart, $searchRequest->limit);
    
        $this->performStringSearch($searchRequest);
        $this->performFilterSearch($searchRequest);
        
        #$this->elasticClient->QueryBuilder->build(true);die;
        
        try {
            $searchResponse->setResults(
                $this->elasticClient->search($searchRequest->indexes ?: SearchData::indexes(), 'search') ?: []
            );
        } catch (\Exception $ex) {
            // if this is an elastic exception, clean the error
            if (substr(get_class($ex), 0, 13) == 'Elasticsearch') {
                $error = json_decode($ex->getMessage());
                $error = $error ? $error->error->root_cause[0]->reason : $ex->getMessage();
                throw new \Exception($error, $ex->getCode(), $ex);
            }
            
            throw $ex;
        }
    }
}
