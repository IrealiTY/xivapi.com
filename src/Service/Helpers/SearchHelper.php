<?php

namespace App\Service\Helpers;

use App\Service\ElasticSearch\Mapping;
use App\Service\Common\Language;

trait SearchHelper
{
    public $columns = false;
    public $documents = [];
    public $schema = [];

    /**
     * Format the content
     */
    public function handle($content)
    {
        $this->buildDocument($content);
        $this->buildDocumentSchema($this->documents[$content->ID]);
    }

    /**
     * Build the document
     */
    public function buildDocument($content)
    {
        if (!$content) {
            print_r($content);
            throw new \Exception("No content ...");
        }

        if (!$this->columns) {
            // Merge columns with some common ones all search data will contain
            $columns = array_merge(
                self::FIELDS,
                [
                    'ID',
                    'Icon',
                    'Url',
                    'GamePatch.ID'
                ]
            );
    
            // handle multi-locale columns
            foreach ($columns as $i => $column) {
                if (stripos($column, '_%s') !== false) {
                    unset($columns[$i]);

                    // add column for each language
                    foreach (Language::LANGUAGES as $lang) {
                        $columns[] = sprintf($column, $lang);
                    }
                }
            }
    
            $columns = array_values($columns);
            $this->columns = $columns;
        }

        // get document data
        foreach ($this->columns as $column) {
            $value = $this->getArrayValue($content, $column, null);
            $this->documents[$content->ID][$column] = $value;
        }
    }

    /**
     * Automatically build the schema from provided content
     */
    public function buildDocumentSchema($content)
    {
        foreach ($content as $field => $value) {
            // ignore empty values
            if (empty($value)) {
                continue;
            }

            // map the schema for the field if it is missing
            if (!isset($this->schema[$field])) {
                $map = Mapping::detect($field, $value);
                $this->schema[$field] = $map;
            }
        }
        
        #print_r($this->schema);die;
    }
}
