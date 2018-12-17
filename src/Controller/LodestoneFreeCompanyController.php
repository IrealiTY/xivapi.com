<?php

namespace App\Controller;

use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Apps\AppManager;
use App\Service\Japan\Japan;
use App\Service\Lodestone\FreeCompanyService;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\FreeCompanyQueue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class LodestoneFreeCompanyController extends Controller
{
    /** @var AppManager */
    private $apps;
    /** @var FreeCompanyService */
    private $service;
    
    public function __construct(AppManager $apps, FreeCompanyService $service)
    {
        $this->apps = $apps;
        $this->service = $service;
    }
    
    /**
     * @Route("/FreeCompany/Search")
     * @Route("/freecompany/search")
     */
    public function search(Request $request)
    {
        $this->apps->fetch($request, true);

        return $this->json(
            Japan::query('/japan/search/freecompany', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/FreeCompany/{lodestoneId}")
     * @Route("/freecompany/{lodestoneId}")
     */
    public function index(Request $request, $lodestoneId)
    {
        $lodestoneId = strtolower(trim($lodestoneId));
        
        if ($lodestoneId < 0 || preg_match("/[a-z]/i", $lodestoneId) || strlen($lodestoneId) < 16 || strlen($lodestoneId) > 20) {
            throw new NotFoundHttpException('Invalid lodestone ID: '. $lodestoneId);
        }

        // choose which content you want
        $data = $request->get('data') ? explode(',', strtoupper($request->get('data'))) : [];
        $content = (object)[
            'FCM' => in_array('FCM', $data),
        ];
    
        $response = (Object)[
            'FreeCompany'        => null,
            'FreeCompanyMembers' => null,
            'Info' => (Object)[
                'FreeCompany'        => null,
                'FreeCompanyMembers' => null,
            ],
        ];

        /** @var FreeCompany $ent */
        [$ent, $freecompany, $times] = $this->service->get($lodestoneId);
        $response->Info->FreeCompany = [
            'State'     => $ent->getState(),
            //'Modified'  => $times[0],
            'Updated'   => $times[1],
        ];
    
        if ($ent->getState() == Entity::STATE_CACHED) {
            $response->FreeCompany = $freecompany;
    
            /** @var FreeCompany $ent */
            if ($content->FCM) {
                [$ent, $members, $times] = $this->service->getMembers($freecompany->ID);
                $response->FreeCompanyMembers = $members;
                $response->Info->FreeCompanyMembers = [
                    'State'     => $ent ? $ent->getState() : Entity::STATE_NONE,
                    //'Modified'  => $times[0],
                    'Updated'   => $times[1],
                ];
            }
        }
    
        return $this->json($response);
    }
    
    /**
     * @Route("/FreeCompany/{lodestoneId}/Delete")
     * @Route("/freecompany/{lodestoneId}/delete")
     */
    public function delete(Request $request, $lodestoneId)
    {
        $this->apps->fetch($request, true);

        /** @var FreeCompany $ent */
        [$ent] = $this->service->get($lodestoneId);
        
        // delete it if the character was not found
        if ($ent->getState() === FreeCompany::STATE_NOT_FOUND) {
            return $this->json($this->service->delete($ent));
        }
    
        return $this->json(false);
    }
    
    /**
     * @Route("/FreeCompany/{lodestoneId}/Update")
     * @Route("/freecompany/{lodestoneId}/update")
     */
    public function update($lodestoneId)
    {
        if ($this->service->cache->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }

        FreeCompanyQueue::request($lodestoneId, 'free_company_update');
        
        $this->service->cache->set(__METHOD__.$lodestoneId, ServiceQueues::UPDATE_TIMEOUT);
        return $this->json(1);
    }
}
