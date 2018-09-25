<?php

namespace App\Service\Search;

class SearchResponse
{
    /** @var SearchRequest */
    private $request;
    /** @var object */
    public $response = [
        'Pagination' => [],
        'Results'    => [],
        'SpeedMs'    => 0,
    ];
    
    public function __construct(SearchRequest $request)
    {
        $this->request = $request;
    }
    
    /**
     * Set results from elastic search
     */
    public function setResults(array $results)
    {
        $this->response = (Object)$this->response;
        
        // no results? return now
        if (!$results) {
            return;
        }
    
        $this->response->SpeedMs    = $results['took'];
        $this->response->Results    = $this->formatResults($results['hits']['hits']);
    
        // Pagination
        $totalResults = (int)$results['hits']['total'];
        $results = count($results['hits']['hits']);
        $pageTotal = $totalResults > 0 ? ceil($totalResults / $this->request->limit) : 0;
        $page = $this->request->page ?: 1;
        $page = $page >= 1 ? $page : 1;
        $pageNext = ($page + 1) <= $pageTotal ? ($page + 1) : null;
        $pagePrev = $page-1 > 0 ? $page-1 : null;
        $this->response->Pagination = [
            'Page'           => $results > 0 ? $page : 0,
            'PageTotal'      => $results > 0 ? $pageTotal : 0,
            'PageNext'       => $results > 0 ? $pageNext : null,
            'PagePrev'       => $results > 0 ? $pagePrev : null,
            'Results'        => $results,
            'ResultsPerPage' => $this->request->limit,
            'ResultsTotal'   => $totalResults,
        ];
    }
    
    /**
     * Format the search results
     */
    public function formatResults($hits)
    {
        $results = [];
        foreach ($hits as $hit) {
            $results[] = $this->buildView($hit);
        }
        
        return $results;
    }
    
    /**
     * Build the search view
     */
    public function buildView($hit)
    {
        $index  = $hit['_index'];
        $source = $hit['_source'];
        
        $row = [ '_' => $index ];
        $view = array_merge(SearchData::views($index), [
            'ID',
            'Icon',
            'Url',
            'GamePatch.ID'
        ]);
        
        foreach ($view as $field) {
            $column = str_ireplace('_%s', null, $field);
            $field  = sprintf($field, $this->request->language);
            $row[$column] = $source[$field] ?? null;
        }
        
        $row['GameType'] = explode('/', $row['Url'])[1];
        $row['_Score'] = $hit['_score'];
        
        ksort($row);
        return $row;
    }
}
