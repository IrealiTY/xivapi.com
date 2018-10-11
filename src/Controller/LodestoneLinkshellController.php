<?php

namespace App\Controller;

use App\Entity\Entity;
use App\Entity\Linkshell;
use App\Service\Apps\AppManager;
use App\Service\Common\GoogleAnalytics;
use App\Service\Helpers\ArrayHelper;
use App\Service\Japan\Japan;
use App\Service\Lodestone\LinkshellService;
use App\Service\Lodestone\ServiceQueues;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
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
    private $appManager;
    /** @var LinkshellService */
    private $service;
    
    public function __construct(AppManager $appManager, LinkshellService $service)
    {
        $this->appManager = $appManager;
        $this->service = $service;
    }
    
    /**
     * @Route("/Linkshell/Search")
     * @Route("/linkshell/search")
     */
    public function search(Request $request)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['Linkshell','Search']);
        
        return $this->json(
            Japan::query('/japan/search/linkshell', [
                'name'   => $request->get('name'),
                'server' => $request->get('server'),
                'page'   => $request->get('page') ?: 1
            ])
        );
    }
    
    /**
     * @Route("/Linkshell/{id}")
     * @Route("/linkshell/{id}")
     */
    public function index(Request $request, $id)
    {
        if ($id < 0) {
            throw new NotFoundHttpException('No, stop it.');
        }

        $start = microtime(true);
        $this->appManager->fetch($request);
    
        $response = (Object)[
            'Linkshell' => null,
            'Info' => (Object)[
                'Linkshell' => null,
            ],
        ];

        /** @var Linkshell $ent */
        [$ent, $linkshell, $times] = $this->service->get($id);
        $response->Info->Linkshell = [
            'State'     => $ent->getState(),
            //'Modified'  => $times[0],
            'Updated'   => $times[1],
        ];
    
        if ($ent->getState() == Entity::STATE_CACHED) {
            $response->Linkshell = $linkshell;
        }
    
        $duration = microtime(true) - $start;
        GoogleAnalytics::hit(['Linkshell',$id]);
        GoogleAnalytics::event('Linkshell', 'get', 'duration', $duration);
        return $this->json($response);
    }
    
    /**
     * @Route("/Linkshell/{id}/Delete")
     * @Route("/linkshell/{id}/delete")
     */
    public function delete(Request $request, $id)
    {
        $app = $this->appManager->fetch($request);

        if ($app->isDefault()) {
            throw new Forbidden403Exception('This route requires an API key');
        }

        /** @var Linkshell $ent */
        [$ent, $data] = $this->service->get($id);
        
        // delete it if the character was not found
        if ($ent->getState() === Linkshell::STATE_NOT_FOUND) {
            return $this->json($this->service->delete($ent));
        }

        GoogleAnalytics::hit(['Linkshell',$id,'Delete']);
        return $this->json(false);
    }
    
    /**
     * @Route("/Linkshell/{id}/Update")
     * @Route("/linkshell/{id}/update")
     */
    public function update(Request $request, $id)
    {
        $this->appManager->fetch($request);

        if ($this->service->cache->get(__METHOD__.$id)) {
            return $this->json(0);
        }
    
        /** @var Linkshell $ent */
        /** @var array $data */
        [$ent, $data] = $this->service->get($id);
        $ent->setUpdated(0);
        $this->service->persist($ent);
    
        $this->service->cache->set(__METHOD__.$id, ServiceQueues::LINKSHELL_UPDATE_TIMEOUT);
        GoogleAnalytics::hit(['Linkshell',$id,'Update']);
        return $this->json(1);
    }
}
