<?php

namespace App\Controller;

use App\Service\Apps\AppManager;
use App\Service\Content\ContentList;
use App\Service\Content\GameServers;
use App\Service\GamePatch\Patch;
use App\Service\Common\GoogleAnalytics;
use App\Service\Redis\Cache;
use App\Utils\ContentNameCaseConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class XivGameContentController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Cache */
    private $cache;
    /** @var ContentList */
    private $contentList;
    /** @var AppManager */
    private $appManager;

    public function __construct(EntityManagerInterface $em, Cache $cache, ContentList $contentList, AppManager $appManager)
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->contentList = $contentList;
        $this->appManager = $appManager;
    }

    /**
     * @Route("/PatchList")
     * @Route("/patchlist")
     */
    public function patches(Request $request)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['PatchList']);
        return $this->json(
            (new Patch())->get()
        );
    }
    
    /**
     * @Route("/Servers")
     * @Route("/servers")
     */
    public function servers(Request $request)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['Servers']);
        return $this->json(GameServers::LIST);
    }
    
    /**
     * @Route("/Servers/DC")
     * @Route("/servers/dc")
     */
    public function serversByDataCenter(Request $request)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['Servers']);
        return $this->json(GameServers::LIST_DC);
    }

    /**
     * @Route("/Content")
     * @Route("/content")
     */
    public function content(Request $request)
    {
        $this->appManager->fetch($request);
        GoogleAnalytics::hit(['Content']);
        return $this->json(
            $this->cache->get('content')
        );
    }

    /**
     * @Route("/{name}")
     */
    public function contentList(Request $request, $name)
    {
        $name = ContentNameCaseConverter::toUpperCase($name);
        if (!$name) {
            throw new NotFoundHttpException("No content data found for: {$name}");
        }
        
        $start = microtime(true);
        $app = $this->appManager->fetch($request);
        
        $content = $this->contentList->get($request, $name, $app);
        
        $duration = microtime(true) - $start;
        GoogleAnalytics::hit([$name]);
        GoogleAnalytics::event('content', 'list', 'duration', $duration);
        return $this->json($content);
    }

    /**
     * @Route("/{name}/schema")
     */
    public function schema(Request $request, $name)
    {
        $name = ContentNameCaseConverter::toUpperCase($name);
        if (!$name) {
            throw new NotFoundHttpException("No content data found for: {$name}");
        }

        $start = microtime(true);
        $this->appManager->fetch($request);

        $content = $this->cache->get("schema_{$name}");

        $duration = microtime(true) - $start;
        GoogleAnalytics::hit([$name]);
        GoogleAnalytics::event('content', 'list', 'duration', $duration);
        return $this->json($content);
    }

    /**
     * @Route("/{name}/{id}")
     * @Route("/{name}/{id}/{seo}")
     */
    public function contentData(Request $request, $name, $id, $seo = null)
    {
        $name = ContentNameCaseConverter::toUpperCase($name);
        if (!$name) {
            throw new NotFoundHttpException("No content data found for: {$name}");
        }
        
        $start = microtime(true);
        $this->appManager->fetch($request);
    
        $content = $this->cache->get("xiv_{$name}_{$id}");
        $content2 = $this->cache->get("xiv2_{$name}_{$id}") ?: (object)[];

        if (!$content) {
            throw new NotFoundHttpException("No content data found for: {$name} {$id}");
        }

        $content = array_merge(
            (array)$content,
            (array)$content2
        );
    
        $duration = microtime(true) - $start;
        GoogleAnalytics::hit([$name, $id]);
        GoogleAnalytics::event('content', 'get', 'duration', $duration);
        return $this->json($content);
    }
}
