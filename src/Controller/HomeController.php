<?php

namespace App\Controller;

use App\Service\Redis\Cache;
use App\Twig\AppExtension;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class HomeController extends Controller
{
    /** @var Cache */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('home.html.twig');
    }
    
    /**
     * todo - this needs to be more.... secure
     * @Route("/maintenance")
     */
    public function maintenance(Request $request)
    {
        if ($request->get('pass') !== getenv('MAINTENANCE_PASS')) {
            throw new UnauthorizedHttpException('Go away');
        }

        /*
        if ($request->get('on')) {
            file_put_contents(__DIR__.'/../offline.txt', $request->get('on'));
            return $this->json(1);
        }
        
        @unlink(__DIR__.'/../offline.txt');
        return $this->json(0);
        */
    }

    /**
     * @Route("/version")
     */
    public function version()
    {
        $ext = new AppExtension();
        return $this->json([
            'version' => $ext->getApiVersion(),
            'hash'    => $ext->getApiHash(),
        ]);
    }
}
