<?php

namespace App\Controller;

use App\Entity\App;
use App\Entity\MapCompletion;
use App\Service\Apps\AppManager;
use App\Service\Maps\Mappy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApplicationsMappyController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var AppManager */
    private $appManager;
    /** @var Mappy */
    private $mappy;
    
    public function __construct(EntityManagerInterface $em, AppManager $appManager, Mappy $mappy)
    {
        $this->em = $em;
        $this->appManager = $appManager;
        $this->mappy = $mappy;
    }
    
    /**
     * @Route("/mappy/verify")
     */
    public function verify()
    {
        return $this->json([
            'allowed' => true
        ]);
    }
    
    /**
     * @Route("/mappy/mark/complete")
     */
    public function markComplete(Request $request)
    {
        $this->appManager->fetch($request, true);

        $repo = $this->em->getRepository(MapCompletion::class);
        $complete = $repo->findOneBy([ 'MapID' => $request->get('map') ]) ?: new MapCompletion();
        
        $complete
            ->setMapID($request->get('map'))
            ->setComplete(true)
            ->setNotes('Marked complete via the app');
        
        $this->em->persist($complete);
        $this->em->flush();
        
        return $this->json([
            'status' => 'complete'
        ]);
    }
    
    /**
     * @Route("/mappy/map/open")
     */
    public function openMap(request $request)
    {
        $app = $this->appManager->fetch($request, true);

        return $this->redirectToRoute('app_manage_map_view', [
            'id' => $app->getId(),
            'map' => $request->get('map')
        ]);
    }
    
    /**
     * @Route("/mappy/submit")
     */
    public function submit(Request $request)
    {
        /** @var App $app */
        $this->appManager->fetch($request, true);
        
        $json = json_decode($request->getContent());
        
        # file_put_contents(__DIR__.'/data'. $json->id .'.json', json_encode($json, JSON_PRETTY_PRINT));
        # $json = json_decode(file_get_contents(__DIR__.'/data839898.json'));

        return $this->json([
            'saved' => $this->mappy->save($json->data)
        ]);
    }
}
