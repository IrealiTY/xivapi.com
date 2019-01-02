<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package App\Controller
 */
class PlayController extends AbstractController
{
    /**
     * @Route("/play/search")
     */
    public function search(Request $request)
    {
        return $this->render('search/play.html.twig');
    }
}
