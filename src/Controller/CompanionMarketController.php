<?php

namespace App\Controller;

use App\Exception\UnauthorizedAccessException;
use App\Service\Apps\AppManager;
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
    const ENDPOINT_CACHE_DURATION = 60;

    /** @var AppManager */
    private $apps;
    /** @var Companion */
    private $companion;
    /** @var Cache */
    private $cache;
    
    public function __construct(AppManager $apps, Companion $companion)
    {
        $this->apps = $apps;
        $this->companion  = $companion;
        $this->cache      = new Cache();
    }
    
    /**
     * @Route("/companion/tokens");
     */
    public function tokens(Request $request)
    {
        if ($request->get('password') != getenv('COMPANION_TOKEN_PASS')) {
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
        $this->apps->fetch($request, true);

        $key = 'companion_market_items_'. md5($server . $itemId);
        if (!$data = $this->cache->get($key)) {
            $data = $this->companion->setServer($server)->getItemPrices($itemId);
            $this->cache->set($key, $data, self::ENDPOINT_CACHE_DURATION);
        }
        
        return $this->json($data);
    }
    
    /**
     * @Route("/market/{server}/items/{itemId}/history")
     */
    public function itemHistory(Request $request, string $server, int $itemId)
    {
        $this->apps->fetch($request, true);

        $key = 'companion_market_items_history_'. md5($server . $itemId);
        if (!$data = $this->cache->get($key)) {
            $data = $this->companion->setServer($server)->getItemHistory($itemId);
            $this->cache->set($key, $data, self::ENDPOINT_CACHE_DURATION);
        }
    
        return $this->json($data);
    }
    
    /**
     * @Route("/market/{server}/category/{category}")
     */
    public function categoryList(Request $request, string $server, int $category)
    {
        $this->apps->fetch($request, true);

        $key = 'companion_market_category_'. md5($server . $category);
        if (!$data = $this->cache->get($key)) {
            $data = $this->companion->setServer($server)->getCategoryList($category);
            $this->cache->set($key, $data, self::ENDPOINT_CACHE_DURATION);
        }
    
        return $this->json($data);
    }
    
    /**
     * @Route("/market/categories")
     */
    public function categories(Request $request)
    {
        $this->apps->fetch($request, true);

        return $this->json(
            $this->companion->getCategories()
        );
    }
}
