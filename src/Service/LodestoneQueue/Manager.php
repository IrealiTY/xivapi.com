<?php

namespace App\Service\LodestoneQueue;

use Lodestone\Api;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager
{
    /** @var SymfonyStyle */
    private $io;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * Process incoming requests FROM xivapi, these will be requests
     * to the sync server asking it to parse various pages, these
     * will be in the queue: [$queue]_requests and be saved back to: [$queue]_response
     * once they have been fulfilled.
     */
    public function processRequests(string $queue): void
    {
        $this->io->title("Processing queue: {$queue}");

        try {
            $requestRabbit  = new RabbitMQ();
            $responseRabbit = new RabbitMQ();

            // connect to the request and response queue
            $requestRabbit->connect("{$queue}_request");
            $responseRabbit->connect("{$queue}_response");

            // read requests
            $requestRabbit->readMessageAsync(function($request) use ($responseRabbit) {
                $this->io->text("{$request->requestId} | {$request->type} | {$request->queue} | Method: {$request->method} args: ". implode(',', $request->arguments));

                // call the API class dynamically and record any exceptions
                try {
                    $request->response = call_user_func_array([new Api(), $request->method], $request->arguments);
                    $request->health = true;
                } catch (\Exception $ex) {
                    $request->response = get_class($ex);
                    $request->health = false;
                }

                // send the request back with the response
                $responseRabbit->sendMessage($request);
            });

            // close connections
            $requestRabbit->close();
            $responseRabbit->close();
        } catch (\Exception $ex) {
            if (get_class($ex) === AMQPTimeoutException::class) {
                $this->io->text('Connection closed automatically');
            } else {
                $this->io->error("Exception Thrown");
                throw $ex;
            }
        }
    }
}
