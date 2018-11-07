<?php

namespace App\Service\Companion;

use Companion\CompanionApi;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This class will log into each character on both accounts
 * and register a 24 usable token. Stick it on a cronjob
 * daily under the command: CompanionAppLoginCommand
 */
class CompanionTokenManager
{
    // list of servers available and the account their on
    const SERVERS = [
        // EU Servers
        'Lich'      => 'COMPANION_APP_ACCOUNT_A',
        'Zodiark'   => 'COMPANION_APP_ACCOUNT_A',
        'Phoenix'   => 'COMPANION_APP_ACCOUNT_A',
    ];
    
    /** @var SymfonyStyle */
    private $io;
    
    public function setSymfonyStyle(SymfonyStyle $io)
    {
        $this->io = $io;
    }
    
    public function go()
    {
        $this->io->title('Companion App API Token Manager');
        
        foreach (self::SERVERS as $server => $account) {
            $this->io->section("Server: {$server}");
            
            // grab username and password
            [$username, $password] = explode(',', getenv($account));
            $this->io->text("Logging into account: {$username}");
            
            // initialize API
            $api = new CompanionApi("xivapi_{$server}");
            $api->Account()->login($username, $password);
            
            // get character list
            $characterId = null;
            $this->io->text("Looking for a character on this server ...");
            foreach ($api->login()->getCharacters()->accounts[0]->characters as $character) {
                if ($character->world == $server) {
                    $characterId = $character->cid;
                    break;
                }
            }
            
            // if not found, error
            if ($characterId === null) {
                $this->io->error("Could not find a character for this server.");
                continue;
            }
            
            // login to the found character
            $this->io->text('Logging into character ...');
            $api->login()->loginCharacter($characterId);
            
            // confirm
            $this->io->text('Confirming login');
            $character = $api->login()->getCharacter()->character;
            if ($characterId !== $character->cid) {
                $this->io->error("Could not login to this character.");
                continue;
            }
            
            // might as well keep those free nuts coming in
            $this->io->text('Free nutz plz');
            $api->payments()->acquirePoints();
            
            // validate login
            $this->io->text('Validating login by requesting Earth Shard history, chances of there being none up?');
            $earthShardSaleCount = count($api->market()->getItemMarketListings(5)->entries);
            
            if ($earthShardSaleCount === 0) {
                $this->io->error("Earth Shard sale count was 0, this is either really unlucky or the character does not have market board access.");
                continue;
            }
           
            // confirm and then sleep a bit before we move onto the next character
            $this->io->text("✔ Server {$server} is all ready to go!");
            $this->io->text("Active token: ". $api->Profile()->getToken());
            $this->io->text('Continue in 3 seconds ...');
            sleep(3);
        }
        
        $this->io->success('All characters have been logged into and tokens set for 24 hours.');
    }
}
