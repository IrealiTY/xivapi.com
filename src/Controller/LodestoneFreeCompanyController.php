<?php

namespace App\Controller;

use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Apps\AppManager;
use App\Service\Common\GoogleAnalytics;
use App\Service\Japan\Japan;
use App\Service\Lodestone\FreeCompanyService;
use App\Service\Lodestone\ServiceQueues;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
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
    private $appManager;
    /** @var FreeCompanyService */
    private $service;
    
    public function __construct(AppManager $appManager, FreeCompanyService $service)
    {
        $this->appManager = $appManager;
        $this->service = $service;
    }
    
    /**
     * @Route("/FreeCompany/Search")
     * @Route("/freecompany/search")
     */
    public function search(Request $request)
    {
        $this->appManager->fetch($request, true);
        GoogleAnalytics::hit(['FreeCompany','Search']);
        
        return $this->json(
            Japan::query('/japan/search/freecompany', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/FreeCompany/{id}")
     * @Route("/freecompany/{id}")
     */
    public function index(Request $request, $id)
    {
        if ($id < 0) {
            throw new NotFoundHttpException('No, stop it.');
        }

        $start = microtime(true);
        $this->appManager->fetch($request);
    
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
        [$ent, $freecompany, $times] = $this->service->get($id);
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
    
        $duration = microtime(true) - $start;
        GoogleAnalytics::hit(['FreeCompany',$id]);
        GoogleAnalytics::event('FreeCompany', 'get', 'duration', $duration);
        return $this->json($response);
    }
    
    /**
     * @Route("/FreeCompany/{id}/Delete")
     * @Route("/freecompany/{id}/delete")
     */
    public function delete(Request $request, $id)
    {
        $this->appManager->fetch($request, true);

        /** @var FreeCompany $ent */
        [$ent, $data] = $this->service->get($id);
        
        // delete it if the character was not found
        if ($ent->getState() === FreeCompany::STATE_NOT_FOUND) {
            return $this->json($this->service->delete($ent));
        }
    
        GoogleAnalytics::hit(['FreeCompany',$id,'Delete']);
        return $this->json(false);
    }
    
    /**
     * @Route("/FreeCompany/{id}/Update")
     * @Route("/freecompany/{id}/update")
     */
    public function update(Request $request, $id)
    {
        $this->appManager->fetch($request);

        if ($this->service->cache->get(__METHOD__.$id)) {
            return $this->json(0);
        }
        
        /** @var FreeCompany $ent */
        /** @var array $data */
        [$ent, $data] = $this->service->get($id);
        $ent->setUpdated(0);
        $this->service->persist($ent);
    
        $this->service->cache->set(__METHOD__.$id, ServiceQueues::FREECOMPANY_UPDATE_TIMEOUT);
        GoogleAnalytics::hit(['FreeCompany',$id,'Update']);
        return $this->json(1);
    }
}
