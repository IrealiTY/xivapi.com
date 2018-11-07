<?php

namespace App\Controller;

use App\Service\Apps\AppManager;
use App\Service\Companion\Companion;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class CompanionMarketController extends Controller
{
    /** @var AppManager */
    private $appManager;
    /** @var Companion */
    private $companion;
    
    public function __construct(AppManager $appManager, Companion $companion)
    {
        $this->appManager = $appManager;
        $this->companion  = $companion;
    }
    
    /**
     * @Route("/market/{server}/items/{itemId}")
     */
    public function itemPrices(Request $request, string $server, int $itemId)
    {
        return $this->json(
            $this->companion->setServer($server)->getItemPrices($itemId)
        );
    }
    
    /**
     * @Route("/market/{server}/items/{itemId}/history")
     */
    public function itemHistory(Request $request, string $server, int $itemId)
    {
    
    }
    
    /**
     * @Route("/market/{server}/category/{catalog}")
     */
    public function categoryList(Request $request, string $server, int $itemId)
    {
    
    }
}
