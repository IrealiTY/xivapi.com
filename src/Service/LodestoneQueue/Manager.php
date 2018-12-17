<?php

namespace App\Service\LodestoneQueue;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Api;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager
{
    /** @var SymfonyStyle */
    private $io;
    /** @var EntityManagerInterface */
    private $em;
    /** @var string */
    private $now;

    public function __construct(SymfonyStyle $io, EntityManagerInterface $em)
    {
        $this->io  = $io;
        $this->em  = $em;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Process incoming requests FROM xivapi, these will be requests
     * to the sync server asking it to parse various pages, these
     * will be in the queue: [$queue]_requests and be saved back to: [$queue]_response
     * once they have been fulfilled.
     */
    public function processRequests(string $queue): void
    {
        $this->io->title("processRequests: {$queue} - Time: {$this->now}");

        try {
            $requestRabbit  = new RabbitMQ();
            $responseRabbit = new RabbitMQ();

            // connect to the request and response queue
            $requestRabbit->connect("{$queue}_request");
            $responseRabbit->connect("{$queue}_response");

            // read requests
            $requestRabbit->readMessageAsync(function($request) use ($responseRabbit) {
                // update times
                $request->updated = microtime(true);
                $this->now = date('Y-m-d H:i:s');
                $this->io->text("{$this->now} {$request->requestId} | {$request->queue} | {$request->method} ". implode(',', $request->arguments) ." - PROCESSING ...");

                // call the API class dynamically and record any exceptions
                try {
                    $request->response = call_user_func_array([new Api(), $request->method], $request->arguments);
                    $request->health = true;
                } catch (\Exception $ex) {
                    $this->io->note("[B] LODESTONE Exception ". get_class($ex) ." at: {$this->now}");
                    print_r($ex->getTrace());
                    $this->io->text('---------------------------------------------');
                    $request->response = get_class($ex);
                    $request->health = false;
                }

                // send the request back with the response
                $responseRabbit->sendMessage($request);
                $this->io->text("{$this->now} {$request->requestId} | {$request->queue} | {$request->method} ". implode(',', $request->arguments) ." - COMPLETE");
            });

            // close connections
            $requestRabbit->close();
            $responseRabbit->close();
        } catch (\Exception $ex) {
            $this->io->note("[A] Exception ". get_class($ex) ." at: {$this->now} = {$ex->getMessage()}");
        }
    }
    
    /**
     * Process response messages back from RabbitMQ
     */
    public function processResponse(string $queue): void
    {
        $this->io->title("processResponse: {$queue} - Time: {$this->now}");

        try {
            $responseRabbit = new RabbitMQ();
            $responseRabbit->connect("{$queue}_response");
            
            // read responses
            $responseRabbit->readMessageAsync(function($response) {
                $this->now = date('Y-m-d H:i:s');

                try {
                    // connect to db
                    $this->em->getConnection()->connect();
                    $this->io->text("{$this->now} {$response->requestId} | {$response->queue} | {$response->method} ". implode(',', $response->arguments) ." | ". ($response->health ? 'Good' : 'Bad') ." - PROCESSING ...");
    
                    // add finished timestamp
                    $response->finished = microtime(true);

                    // handle response based on queue
                    switch($response->queue) {
                        default:
                            $this->io->text("Unknown response queue: {$response->queue}");
                            return;
        
                        case 'character_add':
                        case 'character_update':
                        case 'character_update_0_normal':
                        case 'character_update_1_normal':
                        case 'character_update_2_normal':
                        case 'character_update_3_normal':
                        case 'character_update_4_normal':
                        case 'character_update_5_normal':
                        case 'character_update_0_patreon':
                        case 'character_update_1_patreon':
                        case 'character_update_0_low':
                        case 'character_update_1_low':
                            CharacterQueue::response($this->em, $response);
                            break;
    
                        case 'character_friends_add':
                        case 'character_friends_update':
                        case 'character_friends_update_0_normal':
                        case 'character_friends_update_1_normal':
                        case 'character_friends_update_0_patreon':
                        case 'character_friends_update_1_patreon':
                            CharacterFriendQueue::response($this->em, $response);
                            break;
    
                        case 'character_achievements_add':
                        case 'character_achievements_update':
                        case 'character_achievements_update_0_normal':
                        case 'character_achievements_update_1_normal':
                        case 'character_achievements_update_2_normal':
                        case 'character_achievements_update_3_normal':
                        case 'character_achievements_update_4_normal':
                        case 'character_achievements_update_5_normal':
                        case 'character_achievements_update_0_patreon':
                        case 'character_achievements_update_1_patreon':
                            CharacterAchievementQueue::response($this->em, $response);
                            break;
    
                        case 'free_company_add':
                        case 'free_company_update':
                        case 'free_company_update_0_normal':
                        case 'free_company_update_1_normal':
                        case 'free_company_update_0_patron':
                        case 'free_company_update_1_patron':
                            FreeCompanyQueue::response($this->em, $response);
                            break;
    
                        case 'linkshell_add':
                        case 'linkshell_update':
                        case 'linkshell_update_0_normal':
                        case 'linkshell_update_1_normal':
                        case 'linkshell_update_0_patron':
                        case 'linkshell_update_1_patron':
                            LinkshellQueue::response($this->em, $response);
                            break;
    
                        case 'pvp_team_add':
                        case 'pvp_team_update':
                        case 'pvp_team_update_0_normal':
                        case 'pvp_team_update_1_normal':
                        case 'pvp_team_update_0_patron':
                        case 'pvp_team_update_1_patron':
                            PvPTeamQueue::response($this->em, $response);
                            break;
                    }
    
                    // confirm
                    $this->io->text("{$this->now} {$response->requestId} | {$response->queue} | {$response->method} ". implode(',', $response->arguments) ." | ". ($response->health ? 'Good' : 'Bad') ." - COMPLETE");
                } catch (\Exception $ex) {
                    $this->io->note("[B] Exception ". get_class($ex) ." at: {$this->now} = {$ex->getMessage()}");
                    print_r($ex->getTrace());
                    $this->io->text('---------------------------------------------');
                }
            });
    
            $responseRabbit->close();
        } catch (\Exception $ex) {
            $this->io->note("[C] Exception ". get_class($ex) ." at: {$this->now} = {$ex->getTraceAsString()}");
        }
    }
}
