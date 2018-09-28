<?php

namespace App\Controller;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Entity\PvPTeam;
use App\Exception\ContentGoneException;
use App\Service\Apps\AppManager;
use App\Service\Content\LodestoneData;
use App\Service\Common\GoogleAnalytics;
use App\Service\Japan\Japan;
use App\Service\Lodestone\CharacterService;
use App\Service\Lodestone\FreeCompanyService;
use App\Service\Lodestone\PvPTeamService;
use App\Service\Lodestone\ServiceQueues;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Lodestone\Api;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LodestoneCharacterController extends Controller
{
    use ControllerTrait;
    use ArrayHelper;

    /** @var AppManager */
    private $appManager;
    /** @var CharacterService */
    private $service;
    /** @var FreeCompanyService */
    private $fcService;
    /** @var PvPTeamService */
    private $pvpService;

    public function __construct(AppManager $appManager, CharacterService $service, FreeCompanyService $fcService, PvPTeamService $pvpService)
    {
        $this->appManager = $appManager;
        $this->service    = $service;
        $this->fcService  = $fcService;
        $this->pvpService = $pvpService;
    }

    /**
     * @Route("/Character/Search")
     * @Route("/character/search")
     */
    public function search(Request $request)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['Character','Search']);
        
        return $this->json(
            Japan::query('/japan/search/character', [
                'name'   => $request->get('name'),
                'server' => $request->get('server'),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }

    /**
     * @Route("/Character/{id}")
     * @Route("/character/{id}")
     */
    public function index(Request $request, $id)
    {
        $start = microtime(true);
        $this->appManager->fetch($request);
        
        // choose which content you want
        $data = $request->get('data') ? explode(',', strtoupper($request->get('data'))) : [];
        $content = (object)[
            'AC'  => in_array('AC', $data),
            'FR'  => in_array('FR', $data),
            'FC'  => in_array('FC', $data),
            'FCM' => in_array('FCM', $data),
            'PVP' => in_array('PVP', $data),
        ];
        
        $response = (Object)[
            'Character'          => null,
            'Achievements'       => null,
            'Friends'            => null,
            'FreeCompany'        => null,
            'FreeCompanyMembers' => null,
            'PvPTeam'            => null,
            
            'Info' => (Object)[
                'Character'          => null,
                'Achievements'       => null,
                'Friends'            => null,
                'FreeCompany'        => null,
                'FreeCompanyMembers' => null,
                'PvPTeam'            => null,
            ],
        ];

        /** @var Character $ent */
        [$ent, $character, $times] = $this->service->get($id);
        $response->Info->Character = [
            'State'     => $ent->getState(),
            //'Modified'  => $times[0],
            'Updated'   => $times[1],
        ];
        
        if ($ent->getState() == Entity::STATE_CACHED) {
            $response->Character = $character;
            
            /** @var CharacterAchievements $ent */
            if ($content->AC) {
                [$ent, $achievements, $times] = $this->service->getAchievements($id);
                $response->Achievements = $achievements;
                $response->Info->Achievements = [
                    'State'     => (!$achievements && $ent->getState() == 2) ? Entity::STATE_ADDING : $ent->getState(),
                    //'Modified'  => $times[0],
                    'Updated'   => $times[1],
                ];
            }
            
            /** @var CharacterFriends $ent */
            if ($content->FR) {
                [$ent, $friends, $times] = $this->service->getFriends($id);
                $response->Friends = $friends;
                $response->Info->Friends = [
                    'State'     => (!$friends && $ent->getState() == 2) ? Entity::STATE_ADDING : $ent->getState(),
                    //'Modified'  => $times[0],
                    'Updated'   => $times[1],
                ];
            }
        
            // if character is in an FC
            if ($character->FreeCompanyId) {
                /** @var FreeCompany $ent */
                if ($content->FC) {
                    [$ent, $freecompany, $times] = $this->fcService->get($character->FreeCompanyId);
                    $response->FreeCompany = $freecompany;
                    $response->Info->FreeCompany = [
                        'State'     => $ent ? $ent->getState() : Entity::STATE_NONE,
                        //'Modified'  => $times[0],
                        'Updated'   => $times[1],
                    ];
                }
                
                /** @var FreeCompany $ent */
                if ($content->FCM) {
                    [$ent, $members, $times] = $this->fcService->getMembers($character->FreeCompanyId);
                    $response->FreeCompanyMembers = $members;
                    $response->Info->FreeCompanyMembers = [
                        'State'     => $ent ? $ent->getState() : Entity::STATE_NONE,
                        //'Modified'  => $times[0],
                        'Updated'   => $times[1],
                    ];
                }
            }
    
            // if character is in a PvP Team
            if ($character->PvPTeamId) {
                /** @var PvPTeam $ent */
                if ($content->PVP) {
                    [$ent, $pvpteam, $times] = $this->pvpService->get($character->PvPTeamId);
                    $response->PvPTeam = $pvpteam;
                    $response->Info->PvPTeam = [
                        'State'     => $ent ? $ent->getState() : Entity::STATE_NONE,
                        //'Modified'  => $times[0],
                        'Updated'   => $times[1],
                    ];
                }
            }
        }
    
        $duration = microtime(true) - $start;
        GoogleAnalytics::hit(['Character',$id]);
        GoogleAnalytics::event('Character', 'get', 'duration', $duration);
        return $this->json($response);
    }

    /**
     * @Route("/Character/{id}/Verification")
     * @Route("/character/{id}/verification")
     */
    public function verification(Request $request, $id)
    {
        $app = $this->appManager->fetch($request);

        if ($app->isDefault()) {
            throw new Forbidden403Exception('This route requires an API key');
        }

        $key = __METHOD__ . $id;
        if ($data = $this->service->cache->get($key)) {
            return $data;
        }

        $character = (new Api())->getCharacter($id);
        LodestoneData::verification($character);

        $data = [
            'ID' => $character->ID,
            'Bio' => $character->Bio,
            'VerificationToken' => $character->VerificationToken,
            'VerificationTokenPass' => $character->VerificationTokenPass,
        ];

        // small cache time as it's just to prevent "spam"
        $this->service->cache->set($key, $data, 15);
        GoogleAnalytics::hit(['Character',$id,'Verification']);
        
        return $this->json($data);
    }

    /**
     * @Route("/Character/{id}/Delete")
     * @Route("/character/{id}/delete")
     */
    public function delete(Request $request, $id)
    {
        $app = $this->appManager->fetch($request);

        if ($app->isDefault()) {
            throw new Forbidden403Exception('This route requires an API key');
        }

        /** @var Character $ent */
        [$ent, $data] = $this->service->get($id);

        // delete it if the character was not found
        if ($ent->getState() === Character::STATE_NOT_FOUND) {
            // todo returning void
            return $this->json($this->service->delete($ent));
        }

        if (!$request->get('duplicate')) {
            throw new \Exception("Please provide a lodestoneID for the duplicate parameter.");
        }

        // fetch dupe character
        $dupe = (new Api())->getCharacter($request->get('duplicate'));

        // check some stuff
        if ($dupe->Name === $data->Name && $dupe->Server === $data->Server) {
            $this->service->delete($ent);
            return $this->json(1);
        }
    
        GoogleAnalytics::hit(['Character',$id,'Delete']);
        return $this->json(false);
    }

    /**
     * @Route("/Character/{id}/Update")
     * @Route("/character/{id}/update")
     */
    public function update(Request $request, $id)
    {
        // todo this should bump achievements

        $this->appManager->fetch($request);

        /** @var Character $ent */
        /** @var CharacterAchievements $entFriends */
        /** @var CharacterFriends $entAchievements */
        [$ent, $data] = $this->service->get($id);

        if ($ent->getState() == Entity::STATE_BLACKLISTED) {
            throw new ContentGoneException(
                ContentGoneException::CODE,
                'You cannot update a blacklisted character'
            );
        }

        if ($ent->getId() == Entity::STATE_ADDING) {
            throw new ContentGoneException(
                ContentGoneException::CODE,
                'You cannot update a character that is still being added'
            );
        }

        [$entFriends, $data] = $this->service->getFriends($id);
        [$entAchievements, $data] = $this->service->getAchievements($id);

        if ($this->service->cache->get(__METHOD__.$id)) {
            return $this->json(0);
        }

        // Bump to front
        $ent->setUpdated(0);
        $entFriends->setUpdated(0)->setState(Entity::STATE_CACHED);
        $entAchievements->setUpdated(0)->setState(Entity::STATE_CACHED);
        
        $this->service->persist($ent);
        $this->service->persist($entFriends);
        $this->service->persist($entAchievements);

        $this->service->cache->set(__METHOD__.$id, ServiceQueues::CHARACTER_UPDATE_TIMEOUT);
        GoogleAnalytics::hit(['Character',$id,'Update']);
        
        return $this->json(1);
    }
}
