<?php

namespace App\Command;

use App\Service\Companion\Companion;
use App\Service\Companion\CompanionApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompanionAppLoginCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var CompanionApi */
    private $companion;
    
    public function __construct(CompanionApi $companion, ?string $name = null)
    {
        $this->companion = $companion;
        
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('CompanionAppLoginCommand')
            ->setDescription('Refresh the logged in token for the companion app')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);

        $this->io->text('Refreshing market token');
        $this->companion->auth()->refreshToken();
        $this->complete();
        
        $this->io->text('Logging into the active character');
        $this->companion->auth()->loginCharacter();
        $this->complete();

        $this->io->text('Confirming FCM Token');
        $this->companion->auth()->fcmToken();
        $this->complete();
        
        $token = Companion::getToken();
        print_r($token);
        $this->complete();
        
        $this->io->text('Confirming companion status');
        $status = $this->companion->points()->getStatus();
        print_r($status);
        $this->complete();
        
        $this->io->text('Confirming token against request');
        $response = $this->companion->market()->getItemMarketData(1675);
        $eorzeaDbItemId = $response->response->Payload->Market->Lodestone->ID;
        $this->io->text("{$eorzeaDbItemId} === 'f036916741a'");
        
        if ($eorzeaDbItemId === 'f036916741a') {
            $this->io->success('Token generated successfully. Cached for 24 hours.');
        } else {
            $this->io->error('Token could not be re-generated!');
        }
    }
}
