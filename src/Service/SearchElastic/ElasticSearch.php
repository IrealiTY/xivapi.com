<?php

namespace App\Service\SearchElastic;

use Elasticsearch\ClientBuilder;

class ElasticSearch
{
    const NUMBER_OF_SHARDS   = 1;
    const NUMBER_OF_REPLICAS = 0;
    const MAX_RESULT_WINDOW  = 100000;
    const MAX_BULK_DOCUMENTS = 1000;

    /** @var \Elasticsearch\Client */
    private $client;

    public function __construct()
    {
        if (!getenv('ELASTIC_IP') || !getenv('ELASTIC_PORT')) {
            //return;
        }

        $hosts = sprintf(
            "%s:%s",
            '127.0.0.1', // getenv('ELASTIC_IP'),
            '9200' // getenv('ELASTIC_PORT')
        );

        $this->client = ClientBuilder::create()->setHosts([ $hosts ])->build();

        if (!$this->client) {
            throw new \Exception("Could not connect to ElasticSearch.");
        }
    }

    public function addIndex(string $index, array $mapping, array $settings = []): void
    {
        $settings = array_merge($settings, [
            'number_of_shards'   => self::NUMBER_OF_SHARDS,
            'number_of_replicas' => self::NUMBER_OF_REPLICAS,
            'max_result_window'  => self::MAX_RESULT_WINDOW,
        ]);

        $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => $settings,
                'mappings' => $mapping
            ]
        ]);
    }

    public function deleteIndex(string $index): void
    {
        if ($this->isIndex($index)) {
            $this->client->indices()->delete([
                'index' => $index
            ]);
        }
    }

    public function isIndex(string $index): bool
    {
        return $this->client->indices()->exists([
            'index' => $index
        ]);
    }

    public function addDocument(string $index, string $type, string $id, array $document): void
    {
        $this->client->index([
            'index' => $index,
            'type'  => $type,
            'id'    => $id,
            'body'  => $document
        ]);
    }

    public function bulkDocuments(string $index, string $type, array $documents): void
    {
        foreach (array_chunk($documents, self::MAX_BULK_DOCUMENTS, true) as $docs) {
            $params = [
                'body' => []
            ];

            foreach ($docs as $id => $doc) {
                $base = [
                    'index' => [
                        '_index' => $index,
                        '_type'  => $type,
                        '_id'    => $id,
                    ]
                ];

                $params['body'][] = $base;
                $params['body'][] = $doc;
            }

            $this->client->bulk($params);
        }
    }

    public function getDocument(string $index, string $type, string $id)
    {
        return $this->client->get([
            'index' => $index,
            'type'  => $type,
            'id'    => $id,
        ]);
    }

    public function deleteDocument(string $index, string $type, string $id): void
    {
        $this->client->indices()->delete([
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ]);
    }

    public function search(string $index, string $type, ElasticQuery $elasticQuery)
    {
        return $this->client->search([
            'index' => $index,
            'type'  => $type,
            'body'  => $elasticQuery->getQuery()
        ]);
    }

    public function count(string $index, string $type, ElasticQuery $elasticQuery)
    {
        return $this->client->count([
            'index' => $index,
            'type'  => $type,
            'body'  => $elasticQuery->getQuery()
        ]);
    }
}
