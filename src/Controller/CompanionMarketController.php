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
    public function itemPrices(string $server, int $itemId)
    {
        return $this->json(
            $this->companion->setServer($server)->getItemPrices($itemId)
        );
    }
    
    /**
     * @Route("/market/{server}/items/{itemId}/history")
     */
    public function itemHistory(string $server, int $itemId)
    {
        return $this->json(
            $this->companion->setServer($server)->getItemHistory($itemId)
        );
    }
    
    /**
     * @Route("/market/{server}/category/{category}")
     */
    public function categoryList(string $server, int $category)
    {
        return $this->json(
            $this->companion->setServer($server)->getCategoryList($category)
        );
    }
    
    /**
     * @Route("/market/categories")
     */
    public function categories()
    {
        return $this->json(
            $this->companion->getCategories()
        );
    }
}
