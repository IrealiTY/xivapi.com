<?php

namespace App\Controller;

use App\Entity\Entity;
use App\Entity\PvPTeam;
use App\Service\Apps\AppManager;
use App\Service\Japan\Japan;
use App\Service\Lodestone\PvPTeamService;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\PvPTeamQueue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LodestonePvPTeamController extends Controller
{
    /** @var AppManager */
    private $apps;
    /** @var PvPTeamService */
    private $service;
    
    public function __construct(AppManager $apps, PvPTeamService $service)
    {
        $this->apps = $apps;
        $this->service = $service;
    }
    
    /**
     * @Route("/PvPTeam/Search")
     * @Route("/PvpTeam/Search")
     * @Route("/pvpteam/search")
     */
    public function search(Request $request)
    {
        $this->apps->fetch($request, true);

        return $this->json(
            Japan::query('/japan/search/pvpteam', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/PvPTeam/{lodestoneId}")
     * @Route("/PvpTeam/{lodestoneId}")
     * @Route("/pvpteam/{lodestoneId}")
     */
    public function index(Request $request, $lodestoneId)
    {
        $response = (Object)[
            'PvPTeam' => null,
            'Info' => (Object)[
                'PvPTeam' => null,
            ],
        ];

        /** @var PvPTeam $ent */
        [$ent, $pvpteam, $times] = $this->service->get($lodestoneId);
        $response->Info->PvPTeam = [
            'State'     => $ent->getState(),
            //'Modified'  => $times[0],
            'Updated'   => $times[1],
        ];
        
        if ($ent->getState() == Entity::STATE_CACHED) {
            $response->PvPTeam = $pvpteam;
        }
    
        return $this->json($response);
    }
    
    /**
     * @Route("/PvPTeam/{lodestoneId}/Delete")
     * @Route("/PvpTeam/{lodestoneId}/Delete")
     * @Route("/pvpteam/{lodestoneId}/delete")
     */
    public function delete(Request $request, $lodestoneId)
    {
        $this->apps->fetch($request, true);

        /** @var PvPTeam $ent */
        [$ent, $data] = $this->service->get($lodestoneId);
        
        // delete it if the character was not found
        if ($ent->getState() === PvPTeam::STATE_NOT_FOUND) {
            return $this->json($this->service->delete($ent));
        }

        return $this->json(false);
    }
    
    /**
     * @Route("/PvPTeam/{lodestoneId}/Update")
     * @Route("/PvpTeam/{lodestoneId}/Update")
     * @Route("/pvpteam/{lodestoneId}/update")
     */
    public function update(Request $request, $lodestoneId)
    {
        if ($this->service->cache->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }

        PvPTeamQueue::request($lodestoneId, 'character_update');

        $this->service->cache->set(__METHOD__.$lodestoneId, ServiceQueues::UPDATE_TIMEOUT);
        return $this->json(1);
    }
}
