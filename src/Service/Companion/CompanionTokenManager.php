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
        'Louisoix'  => 'COMPANION_APP_ACCOUNT_A',
        'Moogle'    => 'COMPANION_APP_ACCOUNT_A',
        'Omega'     => 'COMPANION_APP_ACCOUNT_A',
        'Cerberus'  => 'COMPANION_APP_ACCOUNT_A',
        'Odin'      => 'COMPANION_APP_ACCOUNT_A',
        'Ragnarok'  => 'COMPANION_APP_ACCOUNT_A',
        'Shiva'     => 'COMPANION_APP_ACCOUNT_A',
        
        // NA servers
        'Hyperion'  => 'COMPANION_APP_ACCOUNT_A',
        'Famfrit'   => 'COMPANION_APP_ACCOUNT_A',
        'Exodus'    => 'COMPANION_APP_ACCOUNT_A',
        'Ultros'    => 'COMPANION_APP_ACCOUNT_A',
        'Excalibur' => 'COMPANION_APP_ACCOUNT_A',
        'Brynhildr' => 'COMPANION_APP_ACCOUNT_A',
        'Behemoth'  => 'COMPANION_APP_ACCOUNT_A',
        'Malboro'   => 'COMPANION_APP_ACCOUNT_A',

        // JP Servers
        'Kujata'    => 'COMPANION_APP_ACCOUNT_B',
        'Aegis'     => 'COMPANION_APP_ACCOUNT_B',
        'Atomos'    => 'COMPANION_APP_ACCOUNT_B',
        'Unicorn'   => 'COMPANION_APP_ACCOUNT_B',
        'Typhon'    => 'COMPANION_APP_ACCOUNT_B',
        'Ramuh'     => 'COMPANION_APP_ACCOUNT_B',
        'Garuda'    => 'COMPANION_APP_ACCOUNT_B',
        'Carbuncle' => 'COMPANION_APP_ACCOUNT_B',
        'Tonberry'  => 'COMPANION_APP_ACCOUNT_B',
        'Ridill'    => 'COMPANION_APP_ACCOUNT_B',
        'Durandal'  => 'COMPANION_APP_ACCOUNT_B',
        'Ifrit'     => 'COMPANION_APP_ACCOUNT_B',
        'Yojimbo'   => 'COMPANION_APP_ACCOUNT_B', 
        'Fenrir'    => 'COMPANION_APP_ACCOUNT_B',
        'Valefor'   => 'COMPANION_APP_ACCOUNT_B',
    ];
    
    /** @var SymfonyStyle */
    private $io;
    
    public function setSymfonyStyle(SymfonyStyle $io): void
    {
        $this->io = $io;
    }
    
    public function go(string $account): void
    {
        $this->io->title('Companion App API Token Manager');
    
        [$username, $password] = explode(',', getenv($account));
        
        foreach (self::SERVERS as $server => $accountRegistered) {
            // skip characters not for this account
            if ($account != $accountRegistered) {
                continue;
            }
            
            $this->io->section("Server: {$server}");
            
            // grab username and password
            $this->io->text("Logging into account: {$username}");
            
            // initialize API
            $api = new CompanionApi("xivapi_{$server}");
            $api->Profile()->setSavePath(Companion::PROFILE_FILENAME);
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
