<?php

namespace App\Service\Socket;

use App\Service\Search\Search;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Message implements MessageComponentInterface
{
    protected $clients = [];
    protected $clientSearch = [];

    /** @var OutputInterface */
    protected $output;
    /** @var Search */
    protected $search;

    function __construct(Search $search)
    {
        $this->search = $search;
    }

    public function setOutput(OutputInterface $output): Message
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Client opens the site
     */
    public function onOpen(ConnectionInterface $client)
    {
        $this->clients[$client->resourceId] = $client;
        $this->clientSearch[$client->resourceId] = clone $this->search;
    }

    /**
     * Client submits a message
     * @throws
     */
    public function onMessage(ConnectionInterface $client, $message)
    {
        try {
            // get clients search object (or create a new one)
            $search = $this->clientSearch[$client->resourceId] ?? clone $this->search;
            
            print_r($message);
            die;

            // handle websocket request
            $search
                ->getRequest()
                ->handleWebSocketRequest($message);

            // go!
            $search->search();

            // get response
            $response = $search->getResponse()->getResults();
        } catch (\Exception $ex) {
            throw $ex;
        }

        $client->send(json_encode($response));
    }

    /**
     * Client leaves the site
     */
    public function onClose(ConnectionInterface $client)
    {
        unset($this->clients[$client->resourceId]);
        $client->close();
    }

    /**
     * Client gets an error
     * @throws
     */
    public function onError(ConnectionInterface $client, \Exception $ex)
    {
        throw $ex;
    }
}
