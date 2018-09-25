<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Service\Socket\Message;

class RunSearchServerCommand extends Command
{
    /** @var Message */
    private $message;

    function __construct(Message $message)
    {
        $this->message = $message;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:search:run')
            ->setDescription('run search')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('SEARCH SERVICE');
        $this->message->setOutput($output);

        // Create WebSocket Server
        $output->writeln('- Create: WS Server');
        $wsServer = new WsServer($this->message);

        // Create Http Server
        $output->writeln('- Create: HTTP Server');
        $httpServer = new HttpServer($wsServer);

        // Initialize IO Server
        $output->writeln('- Create: IO Server');
        $server = IoServer::factory($httpServer, getenv('SEARCH_PORT'));

        // Run!
        $output->writeln('Running server');
        $server->run();
    }
}
