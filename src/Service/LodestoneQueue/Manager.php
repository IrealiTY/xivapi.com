<?php

namespace App\Service\LodestoneQueue;

use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Api;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager
{
    const LOG_FILENAME = __DIR__ .'/Manager.json';

    /** @var SymfonyStyle */
    private $io;
    /** @var EntityManagerInterface */
    private $em;
    /** @var string */
    private $now;

    public function __construct(SymfonyStyle $io, EntityManagerInterface $em)
    {
        $this->io = $io;
        $this->em = $em;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Process incoming requests FROM xivapi, these will be requests
     * to the sync server asking it to parse various pages, these
     * will be in the queue: [$queue]_requests and be saved back to: [$queue]_response
     * once they have been fulfilled.
     */
    public function processRequests(string $queue): void
    {
        $this->io->title("processRequests: {$queue} - Time: {$this->now}");

        try {
            $requestRabbit  = new RabbitMQ();
            $responseRabbit = new RabbitMQ();

            // connect to the request and response queue
            $requestRabbit->connect("{$queue}_request");
            $responseRabbit->connect("{$queue}_response");

            // read requests
            $requestRabbit->readMessageAsync(function($request) use ($responseRabbit) {
                // update times
                $request->updated = microtime(true);
                $this->now = date('Y-m-d H:i:s');
                $this->io->text("{$this->now} {$request->requestId} | {$request->type} | {$request->queue} | Method: {$request->method} args: ". implode(',', $request->arguments));

                // call the API class dynamically and record any exceptions
                try {
                    $request->response = call_user_func_array([new Api(), $request->method], $request->arguments);
                    $request->health = true;
                } catch (\Exception $ex) {
                    $this->io->error("[B] LODESTONE Exception ". get_class($ex) ." at: {$this->now}");
                    $request->response = get_class($ex);
                    $request->health = false;
                }

                // send the request back with the response
                $responseRabbit->sendMessage($request);
                $this->recordStatistics('processRequests', $request);
            });

            // close connections
            $requestRabbit->close();
            $responseRabbit->close();
        } catch (\Exception $ex) {
            $this->io->error("[A] Exception ". get_class($ex) ." at: {$this->now} = {$ex->getTraceAsString()}");
        }
    }
    
    /**
     * Process response messages back from RabbitMQ
     */
    public function processResponse(string $queue): void
    {
        $this->io->title("processResponse: {$queue} - Time: {$this->now}");

        try {
            $responseRabbit = new RabbitMQ();
            $responseRabbit->connect("{$queue}_response");
            
            // read responses
            $responseRabbit->readMessageAsync(function($response) {
                $this->now = date('Y-m-d H:i:s');

                try {
                    // reconnect to database if it has dropped
                    if (!$this->em->getConnection()->isConnected()) {
                        $this->em->getConnection()->connect();
                        $this->io->text("{$this->now} Reconnected to MySQL.");
                    }

                    $this->io->text("{$this->now} {$response->requestId} | {$response->type} | {$response->queue} | Method: {$response->method} args: ". implode(',', $response->arguments) ." | Heath Status: ". ($response->health ? 'Good' : 'Bad'));
    
                    // add finished timestamp
                    $response->finished = microtime(true);

                    // handle response based on type
                    switch($response->type) {
                        default:
                            $this->io->text("Unknown response type: {$response->type}");
                            return;
        
                        case 'character':
                            CharacterQueue::response($this->em, $response);
                            break;
                    }
                } catch (\Exception $ex) {
                    $this->io->error("[B] Exception ". get_class($ex) ." at: {$this->now} = {$ex->getTraceAsString()}");
                }
            });
    
            $responseRabbit->close();
        } catch (\Exception $ex) {
            $this->io->error("[C] Exception ". get_class($ex) ." at: {$this->now} = {$ex->getTraceAsString()}");
        }
    }

    /**
     * Record some statistics of the logger
     */
    private function recordStatistics($name, $request)
    {
        $time = time();

        // this will only happen first time
        if (!file_exists(self::LOG_FILENAME)) {
            file_put_contents(self::LOG_FILENAME, json_encode([]));
        }

        $stats = json_decode(file_get_contents(self::LOG_FILENAME));
        $stats = empty($stats) ? (Object)[
            'started'   => $this->now,
            'counter'   => 0,
            'perMinute' => 0,
            'perHour'   => 0,
            'perDay'    => 0,
            'entries'   => [],
        ] : $stats;

        // Record stats
        $stats->counter++;

        if (array_key_exists($time, $stats->entries) === false) {
            $stats->entries[$time] = 0;
        }

        // increment time count
        $stats->entries[$time]++;

        // average the last 60 seconds
        $avg = [];
        foreach (range(0,60) as $second) {
            $avg[] = $stats->entries[($time - $second)] ?? 0;
        }
        $stats->perMinute = array_sum($avg) / count($avg);

        // average the last 3600 (1hr) seconds
        $avg = [];
        foreach (range(0,3600) as $second) {
            $avg[] = $stats->entries[($time - $second)] ?? 0;
        }
        $stats->perHour = array_sum($avg) / count($avg);

        // Per day is just average over an hr multiplied, it's roughly... accurate
        $stats->perDay = ($stats->perHour * 24);

        // Clear our entries older than an hour
        foreach ($stats->entries as $time => $count) {
            if ($time < (time() - 3600)) {
                unset($stats->entries[$time]);
            }
        }

        // Save
        file_get_contents(self::LOG_FILENAME, json_encode($stats));
    }
}
