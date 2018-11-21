<?php

namespace App\Service\LodestoneQueue;

use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Api;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager
{
    /** @var SymfonyStyle */
    private $io;
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(SymfonyStyle $io, EntityManagerInterface $em)
    {
        $this->io = $io;
        $this->em = $em;
    }

    /**
     * Process incoming requests FROM xivapi, these will be requests
     * to the sync server asking it to parse various pages, these
     * will be in the queue: [$queue]_requests and be saved back to: [$queue]_response
     * once they have been fulfilled.
     */
    public function processRequests(string $queue): void
    {
        $this->io->title("Processing requests: {$queue}");
        $alive = time();

        try {
            $requestRabbit  = new RabbitMQ();
            $responseRabbit = new RabbitMQ();

            // connect to the request and response queue
            $requestRabbit->connect("{$queue}_request");
            $responseRabbit->connect("{$queue}_response");

            // read requests
            $requestRabbit->readMessageAsync(function($request) use ($responseRabbit) {
                $this->io->text(date('Y-m-d H:i:s') . " {$request->requestId} | {$request->type} | {$request->queue} | Method: {$request->method} args: ". implode(',', $request->arguments));
                // add a timestamp
                $request->updated = microtime(true);
                
                // call the API class dynamically and record any exceptions
                try {
                    $request->response = call_user_func_array([new Api(), $request->method], $request->arguments);
                    $request->health = true;
                } catch (\Exception $ex) {
                    $this->io->error('Exception thrown: '. $ex->getMessage());
                    $request->response = get_class($ex);
                    $request->health = false;
                }

                // send the request back with the response
                $responseRabbit->sendMessage($request);
            });

            // close connections
            $requestRabbit->close();
            $responseRabbit->close();
            $this->io->text(date('Y-m-d H:i:s') . 'processRequests Completed! Alive for: '. time() - $alive);
        } catch (\Exception $ex) {
            if (get_class($ex) === AMQPTimeoutException::class) {
                $this->io->text('Connection closed automatically');
            } else {
                $this->io->error(date('Y-m-d H:i:s') . ' Exception thrown: '. $ex->getMessage());
                throw $ex;
            }
        }
    }
    
    /**
     * Process response messages back from RabbitMQ
     */
    public function processResponse(string $queue): void
    {
        $this->io->title("Processing responses: {$queue}");
        $alive = time();

        try {
            $responseRabbit = new RabbitMQ();
            $responseRabbit->connect("{$queue}_response");
            
            // read responses
            $responseRabbit->readMessageAsync(function($response) {
                try {
                    $this->io->text(date('Y-m-d H:i:s') . " {$response->requestId} | {$response->type} | {$response->queue} | Method: {$response->method} args: ". implode(',', $response->arguments) ." | Heath Status: ". ($response->health ? 'Good' : 'Bad'));
    
                    // add finished timestamp
                    $response->finished = microtime(true);
    
                    // handle response based on type
                    switch($response->type) {
                        default:
                            $this->io->error("Unknown response type: {$response->type}");
                            return;
        
                        case 'character':
                            CharacterQueue::response($this->em, $response);
                            break;
                    }
                } catch (\Exception $ex) {
                    $this->io->error(date('Y-m-d H:i:s') . ' Exception thrown: '. $ex->getMessage());
                }
            });
    
            $responseRabbit->close();
            $this->io->text(date('Y-m-d H:i:s') . 'processResponse Completed Alive for: '. time() - $alive);
        } catch (\Exception $ex) {
            if (get_class($ex) === AMQPTimeoutException::class) {
                $this->io->text('Connection closed automatically');
            } else {
                $this->io->error(date('Y-m-d H:i:s') . ' Exception thrown: '. $ex->getMessage());
                throw $ex;
            }
        }
    }
}