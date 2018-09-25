<?php

namespace App\Service\Helpers;

use App\Service\Redis\Cache;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Command\SaintCoinachRedisCommand;
use App\Service\Data\CsvReader;


class ManualHelper
{
    const REDIS_DURATION = SaintCoinachRedisCommand::REDIS_DURATION;
    
    /** @var SymfonyStyle */
    public $io;
    /** @var Cache */
    public $redis;
    
    public function init(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->io->text('<info>'. get_class($this) .'</info>');
        $this->redis = new Cache();
        return $this;
    }
    
    /**
     * Get keys
     */
    public function getContentIds($contentName)
    {
        $key    = "ids_{$contentName}";
        $keys   = $this->redis->get($key);
        return $keys;
    }
    
    /**
     * Get a CSV
     */
    public function getCsv($filename)
    {
        // todo - this should not use GAME_VERSION
        $path = __DIR__.'/../../..'. getenv('GAME_TOOLS_DIRECTORY') .
            '/SaintCoinach.Cmd/'. getenv('GAME_VERSION') .'/raw-exd-all/';
        
        if (!file_exists($path . $filename)) {
            return false;
        }
        
        $csv = CsvReader::Get($path . $filename);
        $csv = array_splice($csv, 2);
        return $csv;
    }
    
    public function pipeToRedis($data, $count = 100)
    {
        if (count($data) !== $count) {
            return $data;
        }
        
        $this->redis->initPipeline();
        foreach ($data as $key => $content) {
            $this->redis->set($key, $content, self::REDIS_DURATION);
        }
        $this->redis->execPipeline();
        
        unset($data);
        return [];
    }
}
