<?php

namespace App\Service\Search;

/**
 * Handle string search
 */
trait TraitStringSearch
{
    public function performStringSearch(SearchRequest $searchRequest)
    {
        // reset query
        $this->elasticClient->QueryBuilder->resetQuery();

        // do nothing if no string
        if (strlen($searchRequest->string) < 1) {
            return;
        }

        switch($searchRequest->stringAlgo) {
            case SearchRequest::STRING_WILDCARD:
                $this->elasticClient->QueryBuilder->wildcard(
                    $searchRequest->stringColumn,
                    $searchRequest->string .'*'
                );
                break;
    
            case SearchRequest::STRING_WILDCARD_PLUS:
                $this->elasticClient->QueryBuilder->wildcard(
                    $searchRequest->stringColumn,
                    '*'. $searchRequest->string .'*'
                );
                break;

            case SearchRequest::STRING_MULTI_MATCH:
                $this->elasticClient->QueryBuilder->match(
                    $searchRequest->stringColumn,
                    $searchRequest->string,
                    'multi_match', [
                        'type' => 'phrase_prefix',
                        'fields' => [$searchRequest->stringColumn]
                    ]);
                break;

            case SearchRequest::STRING_QUERY_STRING:
                $this->elasticClient->QueryBuilder->match(
                    $searchRequest->stringColumn,
                    $searchRequest->string,
                    'query_string', [
                        'default_field' => $searchRequest->stringColumn,
                        'query' => $searchRequest->string
                    ]);
                break;
    
            case SearchRequest::STRING_PREFIX:
                $this->elasticClient->QueryBuilder->term(
                    $searchRequest->stringColumn,
                    $searchRequest->string,
                    'prefix'
                );
                break;

            case SearchRequest::STRING_TERM:
                $this->elasticClient->QueryBuilder->term(
                    $searchRequest->stringColumn,
                    $searchRequest->string
                );
                break;

            case SearchRequest::STRING_PHRASE_PREFIX:
                $this->elasticClient->QueryBuilder->match(
                    $searchRequest->stringColumn,
                    $searchRequest->string,
                    'match_phrase_prefix'
                );
                break;
    
            case SearchRequest::STRING_PHRASE:
                $this->elasticClient->QueryBuilder->match(
                    $searchRequest->stringColumn,
                    $searchRequest->string,
                    'match_phrase'
                );
                break;

            case SearchRequest::STRING_FUZZY:
                $this->elasticClient->QueryBuilder->fuzzy(
                    $searchRequest->stringColumn,
                    $searchRequest->string,
                    [
                        'boost' => 1.0,
                        'fuzziness' => 2,
                        'prefix_length' => 0,
                        'max_expansions' => 100,
                    ]);
                break;
        }
    }
}
