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
    const SERVERS = [
        // JP Servers
        'Aegis'         => 'COMPANION_APP_ACCOUNT_A',
        'Atomos'        => 'COMPANION_APP_ACCOUNT_A',
        'Carbuncle'     => 'COMPANION_APP_ACCOUNT_A',
        'Garuda'        => 'COMPANION_APP_ACCOUNT_A',
        'Gungnir'       => 'COMPANION_APP_ACCOUNT_A',
        'Kujata'        => 'COMPANION_APP_ACCOUNT_A',
        'Ramuh'         => 'COMPANION_APP_ACCOUNT_A',
        'Tonberry'      => 'COMPANION_APP_ACCOUNT_A',
        'Typhon'        => 'COMPANION_APP_ACCOUNT_A',
        'Unicorn'       => 'COMPANION_APP_ACCOUNT_A',
        'Alexander'     => 'COMPANION_APP_ACCOUNT_A',
        'Bahamut'       => 'COMPANION_APP_ACCOUNT_A',
        'Durandal'      => 'COMPANION_APP_ACCOUNT_A',
        'Fenrir'        => 'COMPANION_APP_ACCOUNT_A',
        'Ifrit'         => 'COMPANION_APP_ACCOUNT_A',
        'Ridill'        => 'COMPANION_APP_ACCOUNT_A',
        'Tiamat'        => 'COMPANION_APP_ACCOUNT_A',
        'Ultima'        => 'COMPANION_APP_ACCOUNT_A',
        'Valefor'       => 'COMPANION_APP_ACCOUNT_A',
        'Yojimbo'       => 'COMPANION_APP_ACCOUNT_A',
        'Zeromus'       => 'COMPANION_APP_ACCOUNT_A',
        'Anima'         => 'COMPANION_APP_ACCOUNT_A',
        'Asura'         => 'COMPANION_APP_ACCOUNT_A',
        'Belias'        => 'COMPANION_APP_ACCOUNT_A',
        'Chocobo'       => 'COMPANION_APP_ACCOUNT_A',
        'Hades'         => 'COMPANION_APP_ACCOUNT_A',
        'Ixion'         => 'COMPANION_APP_ACCOUNT_A',
        'Mandragora'    => 'COMPANION_APP_ACCOUNT_A',
        'Masamune'      => 'COMPANION_APP_ACCOUNT_A',
        'Pandaemonium'  => 'COMPANION_APP_ACCOUNT_A',
        'Shinryu'       => 'COMPANION_APP_ACCOUNT_A',
        'Titan'         => 'COMPANION_APP_ACCOUNT_A',
    
        // US Servers
        'Adamantoise'   => 'COMPANION_APP_ACCOUNT_B',
        'Balmung'       => 'COMPANION_APP_ACCOUNT_B',
        'Cactuar'       => 'COMPANION_APP_ACCOUNT_B',
        'Coeurl'        => 'COMPANION_APP_ACCOUNT_B',
        'Faerie'        => 'COMPANION_APP_ACCOUNT_B',
        'Gilgamesh'     => 'COMPANION_APP_ACCOUNT_B',
        'Goblin'        => 'COMPANION_APP_ACCOUNT_B',
        'Jenova'        => 'COMPANION_APP_ACCOUNT_B',
        'Mateus'        => 'COMPANION_APP_ACCOUNT_B',
        'Midgardsormr'  => 'COMPANION_APP_ACCOUNT_B',
        'Sargatanas'    => 'COMPANION_APP_ACCOUNT_B',
        'Siren'         => 'COMPANION_APP_ACCOUNT_B',
        'Zalera'        => 'COMPANION_APP_ACCOUNT_B',
        'Behemoth'      => 'COMPANION_APP_ACCOUNT_B',
        'Brynhildr'     => 'COMPANION_APP_ACCOUNT_B',
        'Diabolos'      => 'COMPANION_APP_ACCOUNT_B',
        'Excalibur'     => 'COMPANION_APP_ACCOUNT_B',
        'Exodus'        => 'COMPANION_APP_ACCOUNT_B',
        'Famfrit'       => 'COMPANION_APP_ACCOUNT_B',
        'Hyperion'      => 'COMPANION_APP_ACCOUNT_B',
        'Lamia'         => 'COMPANION_APP_ACCOUNT_B',
        'Leviathan'     => 'COMPANION_APP_ACCOUNT_B',
        'Malboro'       => 'COMPANION_APP_ACCOUNT_B',
        'Ultros'        => 'COMPANION_APP_ACCOUNT_B',

        // EU Servers
        'Cerberus'      => 'COMPANION_APP_ACCOUNT_B',
        'Lich'          => 'COMPANION_APP_ACCOUNT_B',
        'Louisoix'      => 'COMPANION_APP_ACCOUNT_B',
        'Moogle'        => 'COMPANION_APP_ACCOUNT_B',
        'Odin'          => 'COMPANION_APP_ACCOUNT_B',
        'Omega'         => 'COMPANION_APP_ACCOUNT_B',
        'Phoenix'       => 'COMPANION_APP_ACCOUNT_B',
        'Ragnarok'      => 'COMPANION_APP_ACCOUNT_B',
        'Shiva'         => 'COMPANION_APP_ACCOUNT_B',
        'Zodiark'       => 'COMPANION_APP_ACCOUNT_B',
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
            $api = new CompanionApi("xivapi_{$server}", Companion::PROFILE_FILENAME);
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
