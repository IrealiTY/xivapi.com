<?php

namespace App\Service\Search;

/**
 * Handle filter search
 */
trait TraitFilterSearch
{
    /**
     * @throws \Exception
     */
    public function performFilterSearch(SearchRequest $searchRequest)
    {
        if (!$searchRequest->filters) {
            return;
        }
    
        $filters = str_getcsv($searchRequest->filters);
        
        foreach ($filters as $filter) {
            preg_match('/(?P<column>[A-Za-z\.]+)(?P<op>(?:=|[<>]=?))(?P<value>\w+)/', $filter, $matches);
            
            $column = $matches['column'] ?? null;
            $op     = $matches['op'] ?? null;
            $value  = $matches['value'] ?? null;
            
            if (!$column || !$op) {
                throw new \Exception("Invalid search filter: {$filter} - It must be: [COLUMN][OPERATOR][VALUE]");
            }
            
            if (in_array($op, ['='])) {
                $this->elasticClient->QueryBuilder->match($column, $value);
            }
    
            if (in_array($op, ['>','<','>=','<='])) {
                $opConversion = [
                    '>=' => 'gte',
                    '<=' => 'lte',
                    '>' => 'gt',
                    '<' => 'lt'
                ];
                
                $this->elasticClient->QueryBuilder->range($column, [
                    $opConversion[$op] => (int)$value
                ]);
            }
        }
    }
}
