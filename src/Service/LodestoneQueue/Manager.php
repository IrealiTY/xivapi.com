<?php

namespace App\Service\LodestoneQueue;

use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager
{
    /** @var SymfonyStyle */
    private $io;
    /** @var RabbitMQ */
    private $rabbit;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->rabbit = new RabbitMQ();
    }

    /**
     * Process incoming messages, this will read messages from
     * RabbitMQ (character adds, updates, fc adds, updates, etc)
     * and process them using the supplied function
     */
    public function incoming(): Manager
    {
        $this->io->title('Processing incoming messages');

        // connect to rabbit mq queue. This could be split off into multiple queues across multiple servers
        $this->rabbit->connect('characters');

        // read messages
        $this->rabbit->readMessageAsync(function($response) {

            print_r($response);

        });

        // close connection
        $this->rabbit->close();

        // if there is an exception, read it.
        /** @var \Exception $ex */
        if ($ex = $this->rabbit->exception) {
            if (get_class($ex) === AMQPTimeoutException::class) {
                $this->io->text('Connection closed automatically');
            } else {
                $this->io->text("<error>--- EXCEPTION ---</error>");
                throw $ex;
            }
        }

        return $this;
    }

    /**
     * Process outgoing messages
     */
    public function outgoing(): Manager
    {
        // todo - get a list of characters to update and queue them up.

        return $this;
    }
}
