<?php

namespace App\Controller;

use App\Exception\UnauthorizedAccessException;
use App\Service\Apps\AppManager;
use App\Service\Common\GoogleAnalytics;
use App\Service\Companion\Companion;
use App\Service\Redis\Cache;
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
    /** @var Cache */
    private $cache;
    
    public function __construct(AppManager $appManager, Companion $companion)
    {
        $this->appManager = $appManager;
        $this->companion  = $companion;
        $this->cache      = new Cache();
    }
    
    /**
     * @Route("/companion/tokens");
     */
    public function tokens(Request $request)
    {
        if ($request->get('access') != getenv('COMPANION_TOKEN_ACCESS')) {
            throw new UnauthorizedAccessException();
        }
        
        return $this->json(
            $this->companion->getTokens()
        );
    }
    
    /**
     * @Route("/market/{server}/items/{itemId}")
     */
    public function itemPrices(Request $request, string $server, int $itemId)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['market',$server,'items',$itemId]);
        
        $key = 'companion_market_items_'. md5($server . $itemId);
        
        if (!$data = $this->cache->get($key)) {
            $data = $this->companion->setServer($server)->getItemPrices($itemId);
            $this->cache->set($key, $data, 15);
        }
        
        return $this->json($data);
    }
    
    /**
     * @Route("/market/{server}/items/{itemId}/history")
     */
    public function itemHistory(Request $request, string $server, int $itemId)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['market',$server,'items',$itemId,'history']);
    
        $key = 'companion_market_items_history_'. md5($server . $itemId);
    
        if (!$data = $this->cache->get($key)) {
            $data = $this->companion->setServer($server)->getItemHistory($itemId);
            $this->cache->set($key, $data, 15);
        }
    
        return $this->json($data);
    }
    
    /**
     * @Route("/market/{server}/category/{category}")
     */
    public function categoryList(Request $request, string $server, int $category)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['market',$server,'category',$category]);
    
        $key = 'companion_market_category_'. md5($server . $category);
    
        if (!$data = $this->cache->get($key)) {
            $data = $this->companion->setServer($server)->getCategoryList($category);
            $this->cache->set($key, $data, 15);
        }
    
        return $this->json($data);
    }
    
    /**
     * @Route("/market/categories")
     */
    public function categories(Request $request)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['market','categories']);
        
        return $this->json(
            $this->companion->getCategories()
        );
    }
}
