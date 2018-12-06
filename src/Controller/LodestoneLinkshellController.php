<?php

namespace App\Controller;

use App\Entity\Entity;
use App\Entity\Linkshell;
use App\Service\Apps\AppManager;
use App\Service\Japan\Japan;
use App\Service\Lodestone\LinkshellService;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\LinkshellQueue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class LodestoneLinkshellController extends Controller
{
    /** @var AppManager */
    private $apps;
    /** @var LinkshellService */
    private $service;
    
    public function __construct(AppManager $apps, LinkshellService $service)
    {
        $this->apps = $apps;
        $this->service = $service;
    }
    
    /**
     * @Route("/Linkshell/Search")
     * @Route("/linkshell/search")
     */
    public function search(Request $request)
    {
        $this->apps->fetch($request, true);

        return $this->json(
            Japan::query('/japan/search/linkshell', [
                'name'   => $request->get('name'),
                'server' => ucwords($request->get('server')),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/Linkshell/{lodestoneId}")
     * @Route("/linkshell/{lodestoneId}")
     */
    public function index(Request $request, $lodestoneId)
    {
        if ($lodestoneId < 0) {
            throw new NotFoundHttpException('No, stop it.');
        }

        $start = microtime(true);
        $this->apps->fetch($request);
    
        $response = (Object)[
            'Linkshell' => null,
            'Info' => (Object)[
                'Linkshell' => null,
            ],
        ];

        /** @var Linkshell $ent */
        [$ent, $linkshell, $times] = $this->service->get($lodestoneId);
        $response->Info->Linkshell = [
            'State'     => $ent->getState(),
            //'Modified'  => $times[0],
            'Updated'   => $times[1],
        ];
    
        if ($ent->getState() == Entity::STATE_CACHED) {
            $response->Linkshell = $linkshell;
        }
    
        $duration = microtime(true) - $start;
        return $this->json($response);
    }
    
    /**
     * @Route("/Linkshell/{lodestoneId}/Delete")
     * @Route("/linkshell/{lodestoneId}/delete")
     */
    public function delete(Request $request, $lodestoneId)
    {
        $this->apps->fetch($request, true);

        /** @var Linkshell $ent */
        [$ent] = $this->service->get($lodestoneId);
        
        // delete it if the character was not found
        if ($ent->getState() === Linkshell::STATE_NOT_FOUND) {
            return $this->json($this->service->delete($ent));
        }

        return $this->json(false);
    }
    
    /**
     * @Route("/Linkshell/{lodestoneId}/Update")
     * @Route("/linkshell/{lodestoneId}/update")
     */
    public function update(Request $request, $lodestoneId)
    {
        if ($this->service->cache->get(__METHOD__.$lodestoneId)) {
            return $this->json(0);
        }

        LinkshellQueue::request($lodestoneId, 'linkshell_update');

        $this->service->cache->set(__METHOD__.$lodestoneId, ServiceQueues::UPDATE_TIMEOUT);
        return $this->json(1);
    }
}
