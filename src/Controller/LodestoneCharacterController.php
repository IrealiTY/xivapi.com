<?php

namespace App\Controller;

use App\Exception\ContentGoneException;
use App\Service\Content\LodestoneData;
use App\Service\Japan\Japan;
use App\Service\Lodestone\CharacterService;
use App\Service\Lodestone\FreeCompanyService;
use App\Service\Lodestone\PvPTeamService;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\CharacterAchievementQueue;
use App\Service\LodestoneQueue\CharacterFriendQueue;
use App\Service\LodestoneQueue\CharacterQueue;
use Lodestone\Api;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LodestoneCharacterController extends Controller
{
    /** @var CharacterService */
    private $service;
    /** @var FreeCompanyService */
    private $fcService;
    /** @var PvPTeamService */
    private $pvpService;

    public function __construct(CharacterService $service, FreeCompanyService $fcService, PvPTeamService $pvpService)
    {
        $this->service    = $service;
        $this->fcService  = $fcService;
        $this->pvpService = $pvpService;
    }
    
    /**
     * todo - temp
     * @Route("/character/{lodestoneId}/add")
     */
    public function add($lodestoneId)
    {
        CharacterQueue::request($lodestoneId, 'character_add');
        CharacterFriendQueue::request($lodestoneId, 'character_friends_add');
        CharacterAchievementQueue::request($lodestoneId, 'character_achievements_add');
        return $this->json(1);
    }
    

    /**
     * @Route("/Character/Search")
     * @Route("/character/search")
     */
    public function search(Request $request)
    {
        return $this->json(
            Japan::query('/japan/search/character', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }

    /**
     * @Route("/Character/{lodestoneId}")
     * @Route("/character/{lodestoneId}")
     */
    public function index(Request $request, $lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));

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
            'Character'             => null,
            'Achievements'          => null,
            'Friends'               => null,
            'FreeCompany'           => null,
            'FreeCompanyMembers'    => null,
            'PvPTeam'               => null,
            'Info' => (Object)[
                'Character'          => null,
                'Achievements'       => null,
                'Friends'            => null,
                'FreeCompany'        => null,
                'FreeCompanyMembers' => null,
                'PvPTeam'            => null,
            ],
        ];

        $character = $this->service->get($lodestoneId, $request->get('extended'));
        $response->Character = $character->data;
        $response->Info->Character = [
            'State'     => $character->ent->getState(),
            'Updated'   => $character->ent->getUpdated()
        ];

        // achievements
        if ($content->AC) {
            $achievements = $this->service->getAchievements($lodestoneId, $request->get('extended'));
            $response->Achievements = $achievements->data;
            $response->Info->Achievements = [
                'State'     => $achievements->ent->getState(),
                'Updated'   => $achievements->ent->getUpdated()
            ];
        }
        
        // friends
        if ($content->FR) {
            $friends = $this->service->getFriends($lodestoneId);
            $response->Friends = $friends->data;
            $response->Info->Friends = [
                'State'     => $friends->ent->getState(),
                'Updated'   => $friends->ent->getUpdated()
            ];
        }
        
        // free company
        if (isset($character->data->FreeCompanyId)) {
            if ($content->FC) {
                $freecompany = $this->fcService->get($character->data->FreeCompanyId);
                $response->FreeCompany = $freecompany->data;
                $response->Info->FreeCompany = [
                    'State'     => $freecompany->ent->getState(),
                    'Updated'   => $freecompany->ent->getUpdated()
                ];
            }
            
            if ($content->FCM) {
                $members = $this->fcService->getMembers($character->data->FreeCompanyId);
                $response->FreeCompanyMembers = $members->data;
                $response->Info->FreeCompanyMembers = [
                    'State'     => $members->ent->getState(),
                    'Updated'   => $members->ent->getUpdated()
                ];
            }
        }

        // if character is in a PvP Team
        if (isset($character->data->PvPTeamId)) {
            if ($content->PVP) {
                $pvp = $this->pvpService->get($character->data->PvPTeamId);
                $response->PvPTeam = $pvp->data;
                $response->Info->PvPTeam = [
                    'State'     => $pvp->ent->getState(),
                    'Updated'   => $pvp->ent->getUpdated()
                ];
            }
        }
    
        return $this->json($response);
    }

    /**
     * @Route("/Character/{lodestoneId}/Verification")
     * @Route("/character/{lodestoneId}/verification")
     */
    public function verification($lodestoneId)
    {
        $character = $this->service->get($lodestoneId);
    
        if ($character->ent->isBlackListed) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Blacklisted');
        }
    
        if ($character->ent->isAdding()) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Not Added');
        }
        
        $key = __METHOD__ . $lodestoneId;
        if ($data = $this->service->cache->get($key)) {
            return $this->json($data);
        }

        $character = (new Api())->getCharacter($lodestoneId);
        LodestoneData::verification($character);

        $data = [
            'ID'                    => $character->ID,
            'Bio'                   => $character->Bio,
            'VerificationToken'     => $character->VerificationToken,
            'VerificationTokenPass' => $character->VerificationTokenPass,
        ];

        // small cache time as it's just to prevent "spam"
        $this->service->cache->set($key, $data, 15);
        return $this->json($data);
    }

    /**
     * @Route("/Character/{lodestoneId}/Update")
     * @Route("/character/{lodestoneId}/update")
     */
    public function update($lodestoneId)
    {
        $character = $this->service->get($lodestoneId);
    
        if ($character->ent->isBlackListed) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Blacklisted');
        }
    
        if ($character->ent->isAdding()) {
            throw new ContentGoneException(ContentGoneException::CODE, 'Not Added');
        }

        if ($lodestoneId != 730968 && $this->service->cache->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }
    
        // send a request to rabbit mq to add this character
        CharacterQueue::request($lodestoneId, 'character_update');
        CharacterFriendQueue::request($lodestoneId, 'character_friends_update');
        CharacterAchievementQueue::request($lodestoneId, 'character_achievements_update');

        $this->service->cache->set(__METHOD__.$lodestoneId, 1, ServiceQueues::UPDATE_TIMEOUT);
        return $this->json(1);
    }
}
