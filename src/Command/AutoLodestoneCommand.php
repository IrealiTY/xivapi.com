<?php

namespace App\Command;

use App\Service\Lodestone\ServiceQueues;
use App\Service\Redis\Cache;
use Lodestone\Api;
use Lodestone\Entity\Character\CharacterSimple;
use Lodestone\Exceptions\AchievementsPrivateException;
use Lodestone\Exceptions\MaintenanceException;
use Lodestone\Exceptions\NotFoundException;
use Lodestone\Game\AchievementsCategory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 *  PROD:
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone ADD_CHARACTERS
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone UPDATE_CHARACTERS
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone UPDATE_ACHIEVEMENTS
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone UPDATE_FRIENDS
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone ADD_FREE_COMPANY
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone UPDATE_FREE_COMPANY
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone ADD_LINKSHELL
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone UPDATE_LINKSHELL
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone ADD_PVPTEAM
 *      * * * * * /usr/bin/php /home/dalamud/dalamud/bin/console AutoLodestone UPDATE_PVPTEAM
 *
 *  DEV:
 *      php bin/console AutoLodestone ALL
 */
class AutoLodestoneCommand extends Command
{
    /** @var Cache */
    private $cache;
    /** @var Api */
    private $api;
    /** @var SymfonyStyle */
    private $io;

    public function __construct(?string $name = null,Cache $cache)
    {
        parent::__construct($name);
        $this->cache = $cache->connect('REDIS_SERVER_LOCAL');
        $this->api   = new Api();
    }

    protected function configure()
    {
        $this->setName('AutoLodestoneCommand')
            ->setDescription("Auto parse The Lodestone")
            ->addArgument('action', InputArgument::REQUIRED, "An auto-queue action to perform");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = time();
        $this->io = new SymfonyStyle($input, $output);

        $action = strtoupper($input->getArgument('action'));
        $this->io->title(__CLASS__ .' - '. __METHOD__);
        $this->io->text("Auto Action: {$action}");

        switch ($action) {
            default:
                throw new \Exception('No action provided');
                break;

            case 'ADD_CHARACTERS':
                $this->handle('parseCharacters', ServiceQueues::CACHE_CHARACTER_QUEUE .'_add');
                break;

            case 'UPDATE_CHARACTERS':
                $this->handle('parseCharacters', ServiceQueues::CACHE_CHARACTER_QUEUE);
                break;

            case 'UPDATE_ACHIEVEMENTS':
                $this->handle('parseAchievements', ServiceQueues::CACHE_ACHIEVEMENTS_QUEUE);
                break;

            case 'UPDATE_FRIENDS':
                $this->handle('parseFriends', ServiceQueues::CACHE_FRIENDS_QUEUE);
                break;

            case 'ADD_FREE_COMPANY':
                $this->handle('parseFreeCompany', ServiceQueues::CACHE_FREECOMPANY_QUEUE .'_add');
                break;

            case 'UPDATE_FREE_COMPANY':
                $this->handle('parseFreeCompany', ServiceQueues::CACHE_FREECOMPANY_QUEUE);
                $this->handle('parseFreeCompanyMembers', ServiceQueues::CACHE_FREECOMPANY_MEMBERS_QUEUE);
                break;

            case 'ADD_LINKSHELL':
                $this->handle('parseLinkshell', ServiceQueues::CACHE_LINKSHELL_QUEUE .'_add');
                break;

            case 'UPDATE_LINKSHELL':
                $this->handle('parseLinkshell', ServiceQueues::CACHE_LINKSHELL_QUEUE);
                break;

            case 'ADD_PVPTEAM':
                $this->handle('parsePvpTeam', ServiceQueues::CACHE_PVPTEAM_QUEUE .'_add');
                break;

            case 'UPDATE_PVPTEAM':
                $this->handle('parsePvpTeam', ServiceQueues::CACHE_PVPTEAM_QUEUE);
                break;
                
            case 'ALL':
                $this->handle('parseCharacters', ServiceQueues::CACHE_CHARACTER_QUEUE .'_add');
                $this->handle('parseCharacters', ServiceQueues::CACHE_CHARACTER_QUEUE);
                $this->handle('parseAchievements', ServiceQueues::CACHE_ACHIEVEMENTS_QUEUE);
                $this->handle('parseFriends', ServiceQueues::CACHE_FRIENDS_QUEUE);
                $this->handle('parseFreeCompany', ServiceQueues::CACHE_FREECOMPANY_QUEUE .'_add');
                $this->handle('parseFreeCompany', ServiceQueues::CACHE_FREECOMPANY_QUEUE);
                $this->handle('parseFreeCompanyMembers', ServiceQueues::CACHE_FREECOMPANY_MEMBERS_QUEUE);
                $this->handle('parseLinkshell', ServiceQueues::CACHE_LINKSHELL_QUEUE .'_add');
                $this->handle('parseLinkshell', ServiceQueues::CACHE_LINKSHELL_QUEUE);
                $this->handle('parsePvpTeam', ServiceQueues::CACHE_PVPTEAM_QUEUE .'_add');
                $this->handle('parsePvpTeam', ServiceQueues::CACHE_PVPTEAM_QUEUE);
                break;
    
            case 'CLEAR':
                $this->clear(ServiceQueues::CACHE_CHARACTER_QUEUE .'_add');
                $this->clear(ServiceQueues::CACHE_CHARACTER_QUEUE);
                $this->clear(ServiceQueues::CACHE_ACHIEVEMENTS_QUEUE);
                $this->clear(ServiceQueues::CACHE_FRIENDS_QUEUE);
                $this->clear(ServiceQueues::CACHE_FREECOMPANY_QUEUE .'_add');
                $this->clear(ServiceQueues::CACHE_FREECOMPANY_QUEUE);
                $this->clear(ServiceQueues::CACHE_FREECOMPANY_MEMBERS_QUEUE);
                $this->clear(ServiceQueues::CACHE_LINKSHELL_QUEUE .'_add');
                $this->clear(ServiceQueues::CACHE_LINKSHELL_QUEUE);
                $this->clear(ServiceQueues::CACHE_PVPTEAM_QUEUE .'_add');
                $this->clear(ServiceQueues::CACHE_PVPTEAM_QUEUE);
                break;
        }

        $duration = time() - $start;
        $this->io->text("<comment>Finished: {$duration}s</comment>");
    }
    
    private function clear($queue)
    {
        $keys = (Object)[
            'request'  => "{$queue}_req",
            'response' => "{$queue}_res",
        ];
        
        $this->cache->delete($keys->request);
        $this->cache->delete($keys->response);
        $this->io->text("Deleted req/res for: {$queue}");
    }

    /**
     * handle an auto-parser action
     */
    private function handle($method, $queue)
    {
        $keys = (Object)[
            'request'  => "{$queue}_req",
            'response' => "{$queue}_res",
        ];

        $request = $this->cache->get($keys->request);

        if (!$request) {
            $this->io->text("No request data for key: {$keys->request}");
            return;
        }

        foreach ($request as $obj) {
            // Out of time!
            if ($this->outOfTime()) break;

            try {
                $obj->data = $this->$method($obj);
            } catch (\Exception $ex) {
                $this->io->text('EXCEPTION: '. get_class($ex));
                $obj->data = $this->handleException($ex);
            }
        }

        // delete request queue and set response queue
        $this->cache
            ->delete($keys->request)
            ->set($keys->response, $request, (60*60));
    }

    /**
     * handle an auto-parser exception
     */
    private function handleException(\Exception $ex)
    {
        switch(get_class($ex)) {
            // report not found
            case NotFoundException::class;
                return NotFoundException::class;
                break;

            // kill script if we're on maintenance
            case MaintenanceException::class;
                throw $ex;
                break;
        }
    }

    /**
     * Check if the current script is out of time and should kill loops
     */
    private function outOfTime()
    {
        return (int)date('s') > ServiceQueues::TIME_LIMIT;
    }

    private function parseCharacters($obj)
    {
        $this->io->text("Parse Character: {$obj->id}");
        return $this->api->getCharacter($obj->id)->toArray();
    }

    private function parseAchievements($obj)
    {
        $this->io->text("Parse Achievements: {$obj->id}");

        // get first page
        $data = $this->api->getCharacterAchievements($obj->id, 1);

        // if no total points, they're private
        if ($data->PointsTotal === 0) {
            $this->io->text("- Private");
            return AchievementsPrivateException::class;
        }

        // process rest of achievements
        $achievements = (Object)[
            'ParseDate' => $data->ParseDate,
            'Points'    => $data->PointsObtained,
            'List'      => [],
        ];
        
        foreach ($data->Achievements as $d) {
            $achievements->List[] = [
                'ID'   => $d->ID,
                'Date' => $d->ObtainedTimestamp,
            ];
        }
        
        foreach (AchievementsCategory::LIST as $catId => $catName) {
            if ($catId === 1) continue;

            try {
                $this->io->write(" {$catId} ");
                $data = $this->api->getCharacterAchievements($obj->id, $catId);
    
                foreach ($data->Achievements as $d) {
                    $achievements->List[] = [
                        'ID'   => $d->ID,
                        'Date' => $d->ObtainedTimestamp,
                    ];
                }
            } catch (\Exception $ex) {
                if ($catId !== 13) {
                    throw $ex;
                }
            }
        }

        $this->io->text(' ');
        return $achievements;
    }

    private function parseFriends($obj)
    {
        $this->io->text("Parse Friends: {$obj->id}");
        $friends = [];
        
        $page    = $this->api->getCharacterFriends($obj->id);
        $friends = array_merge($friends, $page->Results);
        if ($page->Pagination->PageTotal > 1) {
            foreach (range(2, $page->Pagination->PageTotal) as $page) {
                $friends = array_merge($friends, $this->api->getCharacterFriends($obj->id, $page)->Results);
            }
        }
    
        $this->io->text("Parse Followers: {$obj->id}");
        $page    = $this->api->getCharacterFollowing($obj->id);
        $friends = array_merge($friends, $page->Results);
        if ($page->Pagination->PageTotal > 1) {
            foreach (range(2, $page->Pagination->PageTotal) as $page) {
                $friends = array_merge($friends, $this->api->getCharacterFollowing($obj->id, $page)->Results);
            }
        }
        
        /** @var CharacterSimple $friend */
        $temp = [];
        foreach ($friends as $friend) {
            $temp[$friend->ID] = $friend;
        }
        
        return array_values($temp);
    }

    private function parseFreeCompany($obj)
    {
        $this->io->text("Parse Free Company: {$obj->id}");
        return $this->api->getFreeCompany($obj->id)->toArray();
    }

    private function parseFreeCompanyMembers($obj)
    {
        $this->io->text("Parse Free Company: {$obj->id}");
        $members = [];
        $page    = $this->api->getFreeCompanyMembers($obj->id);
        $members = array_merge($members, $page->Results);

        if ($page->Pagination->PageTotal > 1) {
            foreach (range(2, $page->Pagination->PageTotal) as $page) {
                $members = array_merge($members, $this->api->getFreeCompanyMembers($obj->id, $page)->Results);
            }
        }

        return $members;
    }

    private function parseLinkshell($obj)
    {
        $linkshell = (Object)[
            'ID'      => $obj->id,
            'Name'    => null,
            'Server'  => null,
            'Members' => [],
        ];
        
        $this->io->text("Parse Linkshell: {$obj->id}");

        $page = $this->api->getLinkshellMembers($obj->id);
        $linkshell->Name = $page->Profile->Name;
        $linkshell->Server = $page->Profile->Server;
        $linkshell->Members = array_merge($linkshell->Members, $page->Results);
    
        if ($page->Pagination->PageTotal > 1) {
            foreach (range(2, $page->Pagination->PageTotal) as $page) {
                $linkshell->Members = array_merge(
                    $linkshell->Members,
                    $this->api->getLinkshellMembers($obj->id, $page)->Results
                );
            }
        }

        return $linkshell;
    }

    private function parsePvpTeam($obj)
    {
        $this->io->text("Parse PvPTeam: {$obj->id}");
        $page = $this->api->getPvPTeamMembers($obj->id);
    
        return (Object)[
            'ID'      => $obj->id,
            'Name'    => $page->Profile->Name,
            'Server'  => $page->Profile->Server,
            'Crest'   => $page->Profile->Crest,
            'Members' => $page->Results,
        ];
    }
}
