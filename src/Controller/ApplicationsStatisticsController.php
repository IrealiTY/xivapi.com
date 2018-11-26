<?php

namespace App\Controller;

use App\Entity\App;
use App\Entity\User;
use App\Exception\UnauthorizedAccessException;
use App\Service\Apps\AppManager;
use App\Service\Common\Statistics;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ApplicationsStatisticsController extends Controller
{
    /** @var UserService */
    private $userService;
    /** @var AppManager */
    private $appManager;
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(UserService $userService, AppManager $appManager, EntityManagerInterface $em)
    {
        $this->userService = $userService;
        $this->appManager = $appManager;
        $this->em = $em;
    }
    
    /**
     * @Route("/statistics", name="statistics")
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
        if (!$user || $user->getLevel() < 10) {
            throw new UnauthorizedAccessException();
        }
        
        if ($request->get('app')) {
            /** @var App $app */
            $app  = $this->appManager->getByKey($request->get('app'));
            $user = $app->getUser();
            
            if ($request->isMethod('POST')) {
                $ban        = (int)$request->get('ban');
                $restrict   = (int)$request->get('restrict');
                $ratelimit  = (int)$request->get('ratelimit');
                $delete     = (int)$request->get('delete');
                
                if ($ban === 1) {
                    $user->setBanned(true)->setAppsMax(0);
                    $this->em->persist($user);
                }
                
                if ($restrict === 1) {
                    $user->setAppsMax(0);
                    $this->em->persist($user);
                }
                
                if ($ratelimit !== null) {
                    $app->setApiRateLimit($ratelimit);
                    $this->em->persist($app);
                }
                
                if ($delete === 1) {
                    $this->em->remove($app);
                }
                
                $this->em->flush();
                return $this->redirectToRoute('statistics');
            }
        }
        
        return $this->render('statistics/index.html.twig',[
            'report' => Statistics::report(),
            'app'    => $app ?? null,
            'user'   => $user ?? null,
        ]);
    }
}
