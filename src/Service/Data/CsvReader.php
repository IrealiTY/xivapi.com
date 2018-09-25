<?php

namespace App\Service\Data;

use League\Csv\Reader;
use League\Csv\Statement;

class CsvReader
{
    /** @var array */
    protected static $cache = [];
    
    /**
     * Get basic CSV data
     */
    public static function Get($filename)
    {
        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }
        
        // get CSV data
        $csv = Reader::createFromPath($filename);
        
        // parse columns
        $stmt = (new Statement())->offset(0)->limit(1);
        $columns = $stmt->process($csv)->fetchOne();
        
        // parse data
        $data = [];
        foreach((new Statement())->offset(1)->process($csv)->getRecords() as $i => $record) {
            foreach($record as $o => $value) {
                $columnName = $columns[$o];
                $data[$i][$columnName] = $value;
            }
        }
    
        self::$cache[$filename] = $data;
        return $data;
    }
}
