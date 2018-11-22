<?php

namespace App\Service\LodestoneQueue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Some rules:
 * - All messages MUST be JSON
 * - Messages will only be acknowledge in production
 */
class RabbitMQ
{
    const QUEUE_OPTIONS = [
        'passive'       => false,
        'durable'       => false,
        'exclusive'     => false,
        'auto_delete'   => false,
        'nowait'        => false,
        'no_local'      => false,
        'no_ack'        => false,
    ];

    /** @var AMQPStreamConnection */
    private $connection;
    /** @var string */
    private $queue;
    /** @var string */
    public $exception;

    /**
     * Connect to a queue and return this class
     */
    public function connect(string $queue): RabbitMQ
    {
        $this->connection = new AMQPStreamConnection(
            getenv('API_RABBIT_IP'),
            getenv('API_RABBIT_PORT'),
            getenv('API_RABBIT_USERNAME'),
            getenv('API_RABBIT_PASSWORD')
        );

        $this->queue = $queue;
        return $this;
    }

    /**
     * Close the connection
     */
    public function close()
    {
        $this->connection->close();
    }

    /**
     * Read messages asynchronously, requires a class handler for processing messages
     * - If no messages are received in the "duration" period, the script will stop
     * - If the script loop continues past the "timeout" period, the script will stop
     *
     * @param $handler - Must be a callback function that will handle the JSON
     */
    public function readMessageAsync($handler)
    {
        /** @var AMQPChannel $channel */
        $channel = $this->connection->channel();

        // callback function for message, use our handler callback
        $callback = function($message) use ($channel, $handler) {
            $handler(json_decode($message->body));
            $channel->basic_ack($message->delivery_info['delivery_tag']);
        };

        // basic message consumer
        $channel->basic_consume(
            $this->queue,
            null,
            self::QUEUE_OPTIONS['no_local'],
            self::QUEUE_OPTIONS['no_ack'],
            self::QUEUE_OPTIONS['exclusive'],
            self::QUEUE_OPTIONS['nowait'],
            $callback
        );

        // process messages
        while(count($channel->callbacks)) {
            $channel->wait();
        }

        return;
    }

    /**
     * Read a message synchronously, this is slow
     */
    public function readMessageSync()
    {
        /** @var AMQPChannel $channel */
        $channel = $this->connection->channel();
        $message = $channel->basic_get($this->queue);
        $channel->basic_ack($message->delivery_info['delivery_tag']);

        if (!$message) {
            return false;
        }

        // acknowledge the message
        return json_decode($message->body);
    }

    /**
     * Send a message to the queue
     */
    public function sendMessage($message)
    {
        // ensure message is a string, we can pass a string or an array/object
        $message = is_string($message) ? $message : json_encode($message);

        $channel = $this->connection->channel();
        $channel->queue_declare(
            $this->queue,
            self::QUEUE_OPTIONS['passive'],
            self::QUEUE_OPTIONS['durable'],
            self::QUEUE_OPTIONS['exclusive'],
            self::QUEUE_OPTIONS['auto_delete'],
            self::QUEUE_OPTIONS['nowait']
        );
        
        $channel->basic_publish(new AMQPMessage($message), '', $this->queue);
        return $this;
    }
    
    /** @var AMQPChannel */
    private $batchChannel = null;
    public function batchMessage($message)
    {
        // ensure message is a string, we can pass a string or an array/object
        $message = is_string($message) ? $message : json_encode($message);
    
        if ($this->batchChannel === null) {
            $this->batchChannel = $this->connection->channel();
            $this->batchChannel->queue_declare(
                $this->queue,
                self::QUEUE_OPTIONS['passive'],
                self::QUEUE_OPTIONS['durable'],
                self::QUEUE_OPTIONS['exclusive'],
                self::QUEUE_OPTIONS['auto_delete'],
                self::QUEUE_OPTIONS['nowait']
            );
        }
        
        $this->batchChannel->batch_basic_publish(new AMQPMessage($message), '', $this->queue);
        return $this;
    }
    
    /**
     * Send a batch of messages
     */
    public function sendBatch()
    {
        $this->batchChannel->publish_batch();
        return $this;
    }
}
