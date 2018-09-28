<?php

namespace App\Service\ElasticSearch;

use Elasticsearch\ClientBuilder;

class ElasticClient
{
    /** @var QueryBuilder */
    public $QueryBuilder;
    private $client;

    /**
     * Set client
     *
     * @return $this
     */
    function __construct($server = null, $port = null)
    {
        $this->client = $server
            ? ClientBuilder::create()->setHosts([ "{$server}:{$port}" ])->build()
            : ClientBuilder::create()->build();

        if (!$this->client) {
            die("\n\nCould not connect to elastic search\n\n");
        }

        $this->QueryBuilder = new QueryBuilder();
        return $this;
    }

    // -------------------
    // Add
    // -------------------

    /**
     * Add an index
     */
    public function addIndex(string $index, array $mapping, array $settings = [], ?int $shards = 1, ?int $replicas = 0)
    {
        $settings = array_merge($settings, [
            'number_of_shards'   => $shards,
            'number_of_replicas' => $replicas,
            'max_result_window'  => 100000,
        ]);
        
        return $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => $settings,
                'mappings' => $mapping
            ]
        ]);
    }

    /**
     * Add a document
     */
    public function addDocument(string $index, string $type, string $id, array $document)
    {
        return $this->client->index([
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $document
        ]);
    }

    /**
     * Bulk add documents
     */
    public function bulkDocuments(string $index, string $type, array $documents)
    {
        $params = ['body' => []];
        $count = 0;

        foreach($documents as $id => $body) {
            $base = [
                'index' => [
                    '_index' => $index,
                    '_type' => $type,
                    '_id' => $id,
                ]
            ];

            $params['body'][] = $base;
            $params['body'][] = $body;
            $count++;

            if ($count % 1000 == 0) {
                $responses = $this->client->bulk($params);
                $params = ['body' => []];
                unset($responses);
            }
        }

        return $this->client->bulk($params);
    }

    // -------------------
    // Get
    // -------------------

    /**
     * Get a document
     */
    public function getDocument(string $index, string $type, string $id, ?bool $future = false)
    {
        $opts = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ];

        // if using future responses
        if ($future) {
            $opts['client'] = [
                'future' => 'lazy',
            ];
        }

        return $this->client->get($opts);
    }


    // -------------------
    // Delete
    // -------------------

    /**
     * Delete an index
     */
    public function deleteIndex(string $index)
    {
        if ($this->isIndex($index)) {
            return $this->client->indices()->delete([
                'index' => $index,
            ]);
        }
    }

    /**
     * Delete an index
     */
    public function deleteType(string $index, string $type)
    {
        return $this->client->indices()->delete([
            'index' => $index,
            'type' => $type,
        ]);
    }

    /**
     * Delete an index
     */
    public function deleteDocument(string $index, string $type, string $id)
    {
        return $this->client->indices()->delete([
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ]);
    }

    // -------------------
    // Misc
    // -------------------

    public function isIndex(string $index)
    {
        return $this->client->indices()->exists([
            'index' => $index
        ]);
    }

    /**
     * Get the source from a future request
     */
    public function source(array $future)
    {
        return isset($future['_source']) ? $future['_source'] : false;
    }

    public function search(string $index, string $type)
    {
        $body = $this->QueryBuilder->build();

        if (!$body['query']) {
            return false;
        }

        $opts = [
            'index' => $index,
            'type' => $type,
            'body' => $body,
        ];

        return $this->client->search($opts);
    }
}
