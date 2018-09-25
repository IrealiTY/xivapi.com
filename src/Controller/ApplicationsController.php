<?php

namespace App\Controller;

use App\Entity\App;
use App\Entity\MapCompletion;
use App\Entity\MapPosition;
use App\Entity\User;
use App\Form\AppForm;
use App\Service\Apps\AppManager;
use App\Service\Maps\Mappy;
use App\Service\Redis\Cache;
use App\Service\User\Time;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Service\User\UserService;
use App\Service\User\SSO\DiscordSignIn;

class ApplicationsController extends Controller
{
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var UserService */
    private $userService;
    /** @var Session */
    private $session;
    /** @var AppManager */
    private $appManager;
    /** @var Cache */
    private $cache;
    
    public function __construct(
        EntityManagerInterface $em,
        UserService $userService,
        SessionInterface $session,
        AppManager $appManager,
        Cache $cache
    ) {
        $this->em          = $em;
        $this->userService = $userService;
        $this->session     = $session;
        $this->appManager  = $appManager;
        $this->cache       = $cache;
    }
    
    /**
     * @Route("/app", name="app")
     */
    public function index()
    {
        return $this->render('app/index.html.twig');
    }
    
    /**
     * @Route("/app/logout", name="app_logout")
     */
    public function logout()
    {
        $this->userService->deleteCookie();
        return $this->redirectToRoute('home');
    }
    
    /**
     * @Route("/app/login/discord", name="app_login_discord")
     */
    public function loginDiscord(Request $request)
    {
        $url = $this->userService->setSsoProvider(new DiscordSignIn($request))->signIn();
        return $this->redirect($url);
    }
    
    /**
     * @Route("/app/login/discord/success", name="app_login_discord_success")
     */
    public function loginDiscordResponse(Request $request)
    {
        if ($request->get('error') == 'access_denied') {
            return $this->redirectToRoute('app');
        }
        
        $this->userService->setSsoProvider(new DiscordSignIn($request))->authenticate();
        return $this->redirectToRoute('app');
    }
    
    /**
     * @Route("/app/{id}", name="app_manage")
     */
    public function app(Request $request, string $id)
    {
        Time::set($request);
        
        if ($request->get('regen')) {
            $message = 'Your API key has been regenerated, the new one can be seen below.';
        }

        /** @var User $user */
        $user = $this->userService->getUser();

        if (!$user) {
            return $this->redirectToRoute('app');
        }

        if ($id === 'new') {
            $app = $this->appManager->create();
            return $this->redirectToRoute('app_manage', [ 'id' => $app->getId() ]);
        }

        /** @var App $app */
        $app = $this->appManager->get($id);
        if (!$app || $app->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Application not found');
        }

        $form = $this->createForm(AppForm::class, $app);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($app);
            $this->em->flush();
            $message = 'Application information has been updated!';
        }

        return $this->render('app/app.html.twig', [
            'app'     => $app,
            'stats'   => $this->appManager->getStats($app),
            'form'    => $form->createView(),
            'message' => $message ?? false,
        ]);
    }
    
    /**
     * @Route("/app/{id}/map", name="app_manage_map")
     */
    public function appMappy(Request $request, string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app');
        }
    
        /** @var App $app */
        $app = $this->appManager->get($id);
        if (!$app || $app->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Application not found');
        }
        
        $regions        = [];
        $maps           = [];
        $mapsCompleted  = [];
        foreach ($this->cache->get('ids_Map') as $id) {
            /** @var \stdClass $obj */
            $obj = $this->cache->get("xiv_Map_{$id}");
            
            // ignore stuff with no placename
            if (!isset($obj->PlaceName->ID)) {
                continue;
            }
            
            $repo = $this->em->getRepository(MapPosition::class);
            $positions = count($repo->findBy([ 'MapID' => $obj->ID ]));
            
            $map = [
                'ID'            => $obj->ID,
                'Url'           => $obj->Url,
                'MapFilename'   => $obj->MapFilename,
                'SizeFactor'    => $obj->SizeFactor,
                'Positions'     => $positions,
                'PlaceName'     => [
                    'ID'    => $obj->PlaceName->ID,
                    'Name'  => empty($obj->PlaceName->Name_en) ? 'Unknown' : $obj->PlaceName->Name_en,
                ],
                'PlaceNameSub'     => [
                    'ID'    => $obj->PlaceNameSub->ID ?? '-',
                    'Name'  => empty($obj->PlaceNameSub->Name_en) ? '' : $obj->PlaceNameSub->Name_en,
                ],
                'Region'        => [
                    'ID'    => $obj->PlaceNameRegion->ID ?? '-',
                    'Name'  => $obj->PlaceNameRegion->Name_en ?? 'No-Region',
                ],
                'Zone'          => [
                    'ID'    => $obj->TerritoryType->PlaceNameZone->ID ?? '-',
                    'Name'  => $obj->TerritoryType->PlaceNameZone->Name_en ?? 'No-Zone',
                ],
            ];
            
            $maps[$obj->PlaceNameRegion->ID ?? 'Unknown'][] = $map;
            $regions[$obj->PlaceNameRegion->ID ?? 'Unknown'] = $obj->PlaceNameRegion->Name_en;
            
            // get map state
            $repo = $this->em->getRepository(MapCompletion::class);
            $mapCompletion  = $repo->findOneBy([ 'MapID' => $obj->ID ]);
            $mapsCompleted[$obj->ID] = false;
            
            /** @var MapCompletion $complete */
            if ($mapCompletion) {
                $mapsCompleted[$obj->ID] = $mapCompletion->isComplete();
            }
            
        }

        ksort($maps);
        ksort($regions);
        
        return $this->render('app/mappy.html.twig', [
            'allowed'           => in_array($app->getApiKey(), Mappy::KEYS),
            'app'               => $app,
            'maps'              => $maps,
            'regions'           => $regions,
            'mapsCompleted'     => $mapsCompleted,
            'showCompleted'     => !empty($request->get('completed'))
        ]);
    }
    
    /**
     * @Route("/app/{id}/{map}", name="app_manage_map_view")
     */
    public function appMappyView(Request $request, string $id, string $map)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app');
        }
    
        /** @var App $app */
        $app = $this->appManager->get($id);
        if (!$app || $app->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Application not found');
        }
        
        $map = $this->cache->get("xiv_Map_{$map}");
    
        // get completion info
        $repo = $this->em->getRepository(MapCompletion::class);
        $complete = $repo->findOneBy([ 'MapID' => $map->ID ]) ?: new MapCompletion();
        
        return $this->render('app/mappy_view.html.twig', [
            'allowed'   => in_array($app->getApiKey(), Mappy::KEYS),
            'app'       => $app,
            'map'       => $map,
            'complete'  => $complete,
        ]);
    }
    
    /**
     * @Route("/app/{id}/{map}/data", name="app_manage_map_data")
     */
    public function appMappyData(Request $request, string $id, string $map)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app');
        }
    
        /** @var App $app */
        $app = $this->appManager->get($id);
        if (!$app || $app->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Application not found');
        }
        
        $repo = $this->em->getRepository(MapPosition::class);
        $positions = [];
        
        /** @var MapPosition $pos */
        $offset = (int)($request->get('offset'))-2;
        $offset = $offset < 0 ? 0 : $offset;
        
        $size   = $request->get('size');
        foreach ($repo->findBy([ 'MapID' => $map ], [ 'Added' => 'Asc' ], $size, $offset) as $pos) {
            $positions[$pos->getID()] = [
                $pos->getName(),
                $pos->getType(),
                
                // divide by 2 as the map is half the size in the viewer
                $pos->getPixelX() / 2,
                $pos->getPixelY() / 2,
                
                $pos->getPosX(),
                $pos->getPosY()
            ];
        }
        
        return $this->json($positions);
    }
    
    /**
     * @Route("/app/{id}/{map}/update", name="app_manage_map_update")
     */
    public function appMappyUpdate(Request $request, string $id, string $map)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
        if (!$user) {
            return $this->redirectToRoute('app');
        }
    
        /** @var App $app */
        $app = $this->appManager->get($id);
        if (!$app || $app->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Application not found');
        }
        
        $repo = $this->em->getRepository(MapCompletion::class);
        
        // get map completion or
        /** @var MapCompletion $complete */
        $complete = $repo->findOneBy([ 'MapID' => $map ]) ?: new MapCompletion();
        $complete
            ->setMapID($map)
            ->setNotes($request->get('notes'))
            ->setComplete($request->get('complete') == 'on');
        
        $this->em->persist($complete);
        $this->em->flush();
        
        return $this->redirectToRoute('app_manage_map_view', [
            'id' => $id,
            'map' => $map
        ]);
    }
    
    /**
     * @Route("/app/{id}/regenerate", name="app_regenerate")
     */
    public function regenerate(Request $request, string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
    
        if (!$user) {
            return $this->redirectToRoute('app');
        }
    
        /** @var App $app */
        $app = $this->appManager->get($id);
    
        if (!$app) {
            throw new NotFoundHttpException('Application not found');
        }
    
        if ($app->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app');
        }
        
        // generate new key
        $app->generateApiKey();
        $this->em->persist($app);
        $this->em->flush();
        
        return $this->redirectToRoute('app_manage', [
            'id' => $id,
            'regen' => 1,
        ]);
    }
    
    /**
     * @Route("/app/{id}/delete", name="app_delete")
     */
    public function delete(Request $request, string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app');
        }
        
        /** @var App $app */
        $app = $this->appManager->get($id);
        
        if (!$app) {
            throw new NotFoundHttpException('Application not found');
        }
        
        if ($app->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app');
        }
        
        return $this->render('app/delete.html.twig', [
            'app' => $app,
            'url' => $this->generateUrl('app_delete_confirm', [
                'id' => $app->getId(),
            ]),
        ]);
    }

    /**
     * @Route("/app/{id}/delete/confirm", name="app_delete_confirm")
     */
    public function deleteConfirmation(Request $request, string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app');
        }
        
        /** @var App $app */
        $app = $this->appManager->get($id);
    
        if (!$app) {
            throw new NotFoundHttpException('Application not found');
        }
        
        if ($app->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app');
        }
        
        $this->em->remove($app);
        $this->em->flush();
    
        return $this->redirectToRoute('app');
    }
}
