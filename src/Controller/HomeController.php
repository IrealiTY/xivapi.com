<?php

namespace App\Controller;

use App\Service\Common\Maintenance;
use App\Service\Common\SiteVersion;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('home.html.twig');
    }
    
    /**
     * @Route("/maintenance")
     */
    public function maintenance(Request $request)
    {
        Maintenance::handle($request);

        return $this->json([
            'status' => 'ok'
        ]);
    }

    /**
     * @Route("/version")
     */
    public function version()
    {
        $ver = SiteVersion::get();
        return $this->json([
            'Version'   => $ver->version,
            'Hash'      => $ver->hash,
            'Timestamp' => $ver->time
        ]);
    }
}
