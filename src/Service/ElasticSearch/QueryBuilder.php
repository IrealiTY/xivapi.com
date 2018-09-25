<?php

namespace App\Service\ElasticSearch;

class QueryBuilder
{
    protected $query = [];
    protected $body = [];

    /**
     * Reset the query and body
     */
    public function reset(): QueryBuilder
    {
        $this->resetQuery()->resetQuery();
        return $this;
    }

    /**
     * Reset Query Builder
     */
    public function resetQuery(): QueryBuilder
    {
        $this->query = [];
        return $this;
    }

    /**
     * Reset the Body
     */
    public function resetBody(): QueryBuilder
    {
        $this->body = [];
        return $this;
    }

    /**
     * Build search query
     */
    public function build(?bool $show = false): array
    {
        $query = [];
        $query = $this->buildGroup($query, [ 'must', 'match' ]);
        $query = $this->buildGroup($query, [ 'filter', 'filter' ]);
        $query = $this->buildGroup($query, [ 'should', 'term' ]);
        $query = $this->buildCommon($query, 'match_all');
        $query = $this->buildCommon($query, 'type');
        $query = $this->buildCommon($query, 'range');
        $query = $this->buildCommon($query, 'prefix');
        $query = $this->buildCommon($query, 'wildcard');
        $query = $this->buildCommon($query, 'fuzzy');
        
        // if more than 1 field, make a bool
        if (count($query) > 1) {
            $tmp = [ 'bool' => [ 'must' => [] ] ];
            foreach($query as $key => $opt) {
                $tmp['bool']['must'][] = [ $key => $opt ];
            }

            $query = $tmp;
        }

        $query = [
            'query' => $query,
        ];

        // build body and return it
        $query = $this->buildBody($query);

        if ($show) {
            print_r(json_encode($query, JSON_PRETTY_PRINT));
            return $query;
        }

        $this->reset();
        return $query;
    }

    /**
     * Show the current DQL
     */
    public function show()
    {
        $this->build(true);
    }

    /**
     * Build group stuff
     * - must, filter, should
     */
    private function buildGroup(array $query, array $keywords): array
    {
        list($key, $type) = $keywords;

        // if none, do nothing
        if (!isset($this->query[$type])) {
            return $query;
        }

        // if one, add it
        if (count($this->query[$type]) == 1) {
            $query += $this->query[$type][0];
            return $query;
        }

        $query['bool'][$key] = [];
        $query['bool'][$key] += $this->query[$type];
        return $query;
    }

    /**
     * Build common stuff
     * - range, prefix, wildcard, fuzzy
     */
    private function buildCommon(array $query, string $type): array
    {
        if (!isset($this->query[$type])) {
            return $query;
        }

        $query[$type] = $this->query[$type];
        return $query;
    }

    /**
     * Build body stuff
     * - from, size, sort
     */
    private function buildBody(array $query): array
    {
        if (isset($this->body['from'])) {
            $query['from'] = $this->body['from'];
        }

        if (isset($this->body['size'])) {
            $query['size'] = $this->body['size'];
        }

        if (isset($this->body['sort'])) {
            $query['sort'] = [];
            foreach($this->body['sort'] as $entry) {
                $query['sort'][] = $entry;
            }
        }

        return $query;
    }

    // -------------------------------------------

    /**
     * Get everything
     */
    public function all(): QueryBuilder
    {
        $this->query['match_all'] = new \stdClass();
        return $this;
    }

    /**
     * Key can be:
     * - match
     * - match_phrase
     * - match_phrase_prefix (poor-man’s auto-complete)
     * - multi_match [
     *      types: best_fields, most_fields, cross_fields, phrase, phrase_prefix
     *      fields: ['abc','xyz']
     *   ]
     * - query_string [
     *      default_field: name
     *      query: query
     *   ]
     */
    public function match($field, $value, $key = 'match', $opts = []): QueryBuilder
    {
        // handle multi_match
        if ($key == 'multi_match') {
            $this->query['match'][] = [ $key => [ 'query' => $value ] ];
            return $this;
        }
    
        // handle match_phrase
        if ($key == 'match_phrase') {
            $this->query['match'][] = [
                $key => [
                    $field => [
                        'query' => $value,
                        'analyzer' => 'whitespace'
                    ]
                ]
            ];
            return $this;
        }

        // handle query_string
        if ($key == 'query_string') {
            $this->query['match'][] = [ $key => $opts ];
            return $this;
        }

        $this->query['match'][] = [ $key => [ $field => $value ] ];
        return $this;
    }

    /**
     * Key can be:
     * - term
     * - constant_score
     */
    public function term($field, $value, $key = 'term'): QueryBuilder
    {
        $this->query['term'][] = [ $key => [ $field => $value ] ];
        return $this;
    }

    /**
     * Perform a range search on a field
     * Options [
     *      'gte' => x, - Greater than or equal to
     *      'gt' => x, - Greater than
     *      'lte' => y, - Less than or equal to
     *      'lt' => y, - Less than
     * ]
     */
    public function range($field, $options): QueryBuilder
    {
        if (isset($this->query['range'][$field])) {
            $this->query['range'][$field] += $options;
            return $this;
        }

        if (isset($this->query['range'])) {
            $this->query['range'] += [ $field => $options ];
            return $this;
        }

        $this->query['range'] = [ $field => $options ];
        return $this;
    }

    /**
     * Perform an exists search
     */
    public function exists($field): QueryBuilder
    {
        $this->query['exists'] = [ 'field' => $field ];
        return $this;
    }

    /**
     * Perform a prefix search
     */
    public function prefix($field, $value): QueryBuilder
    {
        $this->query['prefix'] = [ $field => $value ];
        return $this;
    }

    /**
     * Perform a wildcard search
     */
    public function wildcard($field, $value, $options = []): QueryBuilder
    {
        if ($options) {
            $options['value'] = $value;
            $this->query['wildcard'] = [ $field => $options ];
        } else {
            $this->query['wildcard'] = [ $field => $value ];
        }

        return $this;
    }

    /**
     * Perform a fuzzy search
     */
    public function fuzzy($field, $value, $options = []): QueryBuilder
    {
        if ($options) {
            $options['value'] = $value;
            $this->query['fuzzy'] = [ $field => $options ];
        } else {
            $this->query['fuzzy'] = [ $field => $value ];
        }

        return $this;
    }

    /**
     * Set the search type
     */
    public function type($value): QueryBuilder
    {
        $this->query['type'] = [ 'value' => $value ];
        return $this;
    }

    /**
     * Filters documents that only have the provided ids
     */
    public function ids($type, $values): QueryBuilder
    {
        $this->query['ids'] = [
            'type' => $type,
            'values' => $values,
        ];
        return $this;
    }

    /**
     * Filter documents against a field and value
     */
    public function filter($field, $value): QueryBuilder
    {
        $this->query['filter'][] = [ 'term' => [ $field => $value ] ];
        return $this;
    }

    /**
     * Add From/Size for pagination
     */
    public function limit($from, $size): QueryBuilder
    {
        $this->body['from'] = $from;
        $this->body['size'] = $size;
        return $this;
    }

    /**
     * Sort against a specific field
     */
    public function sort($field = null, $direction = 'asc'): QueryBuilder
    {
        if (!$field) {
            return $this;
        }
        
        $this->body['sort'] = [];
        $this->body['sort'][] = [ $field => $direction ];
        return $this;
    }
}
