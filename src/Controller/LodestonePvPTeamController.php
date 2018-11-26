<?php

namespace App\Controller;

use App\Entity\Entity;
use App\Entity\PvPTeam;
use App\Service\Apps\AppManager;
use App\Service\Common\GoogleAnalytics;
use App\Service\Japan\Japan;
use App\Service\Lodestone\PvPTeamService;
use App\Service\Lodestone\ServiceQueues;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LodestonePvPTeamController extends Controller
{
    /** @var AppManager */
    private $appManager;
    /** @var PvPTeamService */
    private $service;
    
    public function __construct(AppManager $appManager, PvPTeamService $service)
    {
        $this->appManager = $appManager;
        $this->service = $service;
    }
    
    /**
     * @Route("/PvPTeam/Search")
     * @Route("/PvpTeam/Search")
     * @Route("/pvpteam/search")
     */
    public function search(Request $request)
    {
        $this->appManager->fetch($request, true);
        GoogleAnalytics::hit(['PvPTeam','Search']);
        
        return $this->json(
            Japan::query('/japan/search/pvpteam', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/PvPTeam/{id}")
     * @Route("/PvpTeam/{id}")
     * @Route("/pvpteam/{id}")
     */
    public function index(Request $request, $id)
    {
        $start = microtime(true);
        $this->appManager->fetch($request);
        $response = (Object)[
            'PvPTeam' => null,
            'Info' => (Object)[
                'PvPTeam' => null,
            ],
        ];

        /** @var PvPTeam $ent */
        [$ent, $pvpteam, $times] = $this->service->get($id);
        $response->Info->PvPTeam = [
            'State'     => $ent->getState(),
            //'Modified'  => $times[0],
            'Updated'   => $times[1],
        ];
        
        if ($ent->getState() == Entity::STATE_CACHED) {
            $response->PvPTeam = $pvpteam;
        }
    
        $duration = microtime(true) - $start;
        GoogleAnalytics::hit(['PvPTeam',$id]);
        GoogleAnalytics::event('PvPTeam', 'get', 'duration', $duration);
        return $this->json($response);
    }
    
    /**
     * @Route("/PvPTeam/{id}/Delete")
     * @Route("/PvpTeam/{id}/Delete")
     * @Route("/pvpteam/{id}/delete")
     */
    public function delete(Request $request, $id)
    {
        $this->appManager->fetch($request, true);

        /** @var PvPTeam $ent */
        [$ent, $data] = $this->service->get($id);
        
        // delete it if the character was not found
        if ($ent->getState() === PvPTeam::STATE_NOT_FOUND) {
            return $this->json($this->service->delete($ent));
        }

        GoogleAnalytics::hit(['PvPTeam',$id,'Delete']);
        return $this->json(false);
    }
    
    /**
     * @Route("/PvPTeam/{id}/Update")
     * @Route("/PvpTeam/{id}/Update")
     * @Route("/pvpteam/{id}/update")
     */
    public function update(Request $request, $id)
    {
        $this->appManager->fetch($request);

        if ($this->service->cache->get(__METHOD__.$id)) {
            return $this->json(0);
        }
        
        /** @var PvPTeam $ent */
        /** @var array $data */
        [$ent, $data] = $this->service->get($id);
        $ent->setUpdated(0);
        $this->service->persist($ent);
    
        $this->service->cache->set(__METHOD__.$id, ServiceQueues::PVPTEAM_UPDATE_TIMEOUT);
        GoogleAnalytics::hit(['PvPTeam',$id,'Update']);
        return $this->json(1);
    }
}
