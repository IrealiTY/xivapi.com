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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LodestoneCharacterController extends Controller
{
    /** @var AppManager */
    private $apps;
    /** @var CharacterService */
    private $service;
    /** @var FreeCompanyService */
    private $fcService;
    /** @var PvPTeamService */
    private $pvpService;

    public function __construct(AppManager $apps, CharacterService $service, FreeCompanyService $fcService, PvPTeamService $pvpService)
    {
        $this->apps = $apps;
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
        $this->apps->fetch($request, true);

        return $this->json(
            Japan::query('/japan/search/character', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/Character/{lodestoneId}/add")
     * @Route("/character/{lodestoneId}/add")
     */
    public function add($lodestoneId)
    {
        if ($lodestoneId != 730968 && $this->service->cache->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }
        
        // mark the character as "adding" so that FC + PVP Teams are auto-added
        /** @var Character $ent */
        [$ent] = $this->service->get($lodestoneId);
        if ($ent) {
            $ent->setStateAdding();
            $this->service->em->persist($ent);
            $this->service->em->flush();
        }
        
        $this->service->register($lodestoneId);
        $this->service->cache->set(__METHOD__.$lodestoneId, 1, ServiceQueues::UPDATE_TIMEOUT);
        
        return $this->json(1);
    }

    /**
     * @Route("/Character/{lodestoneId}")
     * @Route("/character/{lodestoneId}")
     */
    public function index(Request $request, $lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));
        
        if ($lodestoneId < 0 || preg_match("/[a-z]/i", $lodestoneId) || strlen($lodestoneId) > 16) {
            throw new NotFoundHttpException('Invalid lodestone ID: '. $lodestoneId);
        }

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
        [$ent, $character] = $this->service->get($lodestoneId);
        if ($ent && $character) {
            $response->Character = $character;
            $response->Info->Character = [
                'State'     => $ent->getState(),
                'Updated'   => $ent->getUpdated()
            ];
    
            // if we're to extend character info
            if ($request->get('extended')) {
                LodestoneData::extendCharacterData($response->Character);
            }
        } else {
            $response->Character = null;
            $response->Info->Character = [
                'State'     => 1,
                'Updated'   => null
            ];
        }

        /** @var CharacterAchievements $ent */
        if ($content->AC) {
            [$ent, $achievements] = $this->service->getAchievements($lodestoneId);
            if ($ent) {
                $response->Achievements = $achievements;
                $response->Info->Achievements = [
                    'State'     => (!$achievements && $ent->getState() == 2) ? Entity::STATE_ADDING : $ent->getState(),
                    'Updated'   => $ent->getUpdated(),
                ];

                // if we're to extend character info
                if ($request->get('extended')) {
                    LodestoneData::extendAchievementData($response->Achievements);
                }
            } else {
                $response->Achievements = null;
                $response->Info->Achievements = [
                    'State'     => 1,
                    'Updated'   => null,
                ];
            }
        }
        
        /** @var CharacterFriends $ent */
        if ($content->FR) {
            [$ent, $friends] = $this->service->getFriends($lodestoneId);
            if ($ent) {
                $response->Friends = $friends;
                $response->Info->Friends = [
                    'State'     => (!$friends && $ent->getState() == 2) ? Entity::STATE_ADDING : $ent->getState(),
                    'Updated'   => $ent->getUpdated()
                ];
            } else {
                $response->Friends = null;
                $response->Info->Friends = [
                    'State'     => 1,
                    'Updated'   => null,
                ];
            }
        }
    
        // if character is in an FC
        if (isset($character->FreeCompanyId)) {
            /** @var FreeCompany $ent */
            if ($content->FC) {
                [$ent, $freecompany] = $this->fcService->get($character->FreeCompanyId);
                if ($ent) {
                    $response->FreeCompany = $freecompany;
                    $response->Info->FreeCompany = [
                        'State'     => $ent ? $ent->getState() : Entity::STATE_NONE,
                        'Updated'   => $ent->getUpdated()
                    ];
                } else {
                    $response->FreeCompany = null;
                    $response->Info->FreeCompany = [
                        'State'     => 0,
                        'Updated'   => null,
                    ];
                }
            }
            
            /** @var FreeCompany $ent */
            if ($content->FCM) {
                [$ent, $members] = $this->fcService->getMembers($character->FreeCompanyId);
                if ($ent) {
                    $response->FreeCompanyMembers = $members;
                    $response->Info->FreeCompanyMembers = [
                        'State'     => $ent ? $ent->getState() : Entity::STATE_NONE,
                        'Updated'   => $ent->getUpdated()
                    ];
                } else {
                    $response->FreeCompanyMembers = null;
                    $response->Info->FreeCompanyMembers = [
                        'State'     => 0,
                        'Updated'   => null,
                    ];
                }
            }
        } else {
            $response->FreeCompany = null;
            $response->Info->FreeCompany = [
                'State'     => 0,
                'Updated'   => null,
            ];
    
            $response->FreeCompanyMembers = null;
            $response->Info->FreeCompanyMembers = [
                'State'     => 0,
                'Updated'   => null,
            ];
        }

        // if character is in a PvP Team
        if (isset($character->PvPTeamId)) {
            /** @var PvPTeam $ent */
            if ($content->PVP) {
                [$ent, $pvpteam] = $this->pvpService->get($character->PvPTeamId);
                if ($ent) {
                    $response->PvPTeam = $pvpteam;
                    $response->Info->PvPTeam = [
                        'State'     => $ent ? $ent->getState() : Entity::STATE_NONE,
                        'Updated'   => $ent->getUpdated()
                    ];
                } else {
                    $response->PvPTeam = null;
                    $response->Info->PvPTeam = [
                        'State'     => 0,
                        'Updated'   => null,
                    ];
                }
            }
        } else {
            $response->PvPTeam = null;
            $response->Info->PvPTeam = [
                'State'     => 0,
                'Updated'   => null,
            ];
        }
    
        return $this->json($response);
    }

    /**
     * @Route("/Character/{lodestoneId}/Verification")
     * @Route("/character/{lodestoneId}/verification")
     */
    public function verification(Request $request, $lodestoneId)
    {
        $this->apps->fetch($request, true);

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
     * @Route("/Character/{lodestoneId}/Delete")
     * @Route("/character/{lodestoneId}/delete")
     */
    public function delete(Request $request, $lodestoneId)
    {
        $this->apps->fetch($request, true);

        /** @var Character $ent */
        [$ent, $data] = $this->service->get($lodestoneId);

        // delete it if the character was not found
        if ($ent->getState() === Character::STATE_NOT_FOUND) {
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
    
        return $this->json(false);
    }

    /**
     * @Route("/Character/{lodestoneId}/Update")
     * @Route("/character/{lodestoneId}/update")
     */
    public function update($lodestoneId)
    {
        /** @var Character $ent */
        /** @var CharacterAchievements $entFriends */
        /** @var CharacterFriends $entAchievements */
        [$ent] = $this->service->get($lodestoneId);

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
