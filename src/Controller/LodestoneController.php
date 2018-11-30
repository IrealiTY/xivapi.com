<?php

namespace App\Controller;

use App\Service\Apps\AppManager;
use App\Service\Redis\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lodestone\Api;

class LodestoneController extends Controller
{
    /** @var AppManager */
    private $appManager;
    /** @var Cache */
    private $cache;

    public function __construct(AppManager $appManager, Cache $cache)
    {
        $this->appManager = $appManager;
        $this->cache = $cache;
    }

    /**
     * @Route("/lodestone")
     * @Route("/Lodestone")
     */
    public function lodestone(Request $request)
    {
        $this->appManager->fetch($request);

        return $this->json(
            $this->cache->get('lodestone')
        );
    }
    
    /**
     * @Route("/lodestone/ids")
     * @Route("/Lodestone/IDs")
     */
    public function lodestoneIDs(Request $request)
    {
        $this->appManager->fetch($request);
        
        $data = file_get_contents(__DIR__.'/../Command/resources/market_items.txt');
        $data = array_filter(explode(PHP_EOL, $data));
        
        $response = [];
        foreach ($data as $line) {
            [$id, $lsid, $icon, $iconhq, $time, $name] = explode("|", $line);
            
            $response[$id] = [
                'ID' => $id,
                'LodestoneID' => $lsid,
                'Icon' => $icon,
                'IconHQ' => $iconhq
            ];
        }
        
        ksort($response);
        
        return $this->json($response);
    }

    /**
     * @Route("/lodestone/banners")
     * @Route("/Lodestone/Banners")
     */
    public function lodestoneBanners(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneBanners();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/news")
     * @Route("/Lodestone/News")
     */
    public function lodestoneNews(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneNews();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/topics")
     * @Route("/Lodestone/Topics")
     */
    public function lodestoneTopics(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneTopics();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/notices")
     * @Route("/Lodestone/Notices")
     */
    public function lodestoneNotices(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneNotices();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/maintenance")
     * @Route("/Lodestone/Maintenance")
     */
    public function lodestoneMaintenance(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneMaintenance();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/updates")
     * @Route("/Lodestone/Updates")
     */
    public function lodestoneUpdates(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneUpdates();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/status")
     * @Route("/Lodestone/Status")
     */
    public function lodestoneStatus(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getLodestoneStatus();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/worldstatus")
     * @Route("/Lodestone/WorldStatus")
     */
    public function lodestoneWorldStatus(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getWorldStatus();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/devblog")
     * @Route("/Lodestone/DevBlog")
     */
    public function lodestoneDevBlog(Request $request)
    {
        $this->appManager->fetch($request);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getDevBlog();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/devposts")
     * @Route("/Lodestone/DevPosts")
     */
    public function lodestoneDevPosts(Request $request)
    {
        $this->appManager->fetch($request, true);

        if (!$data = $this->cache->get(__METHOD__)) {
            $data = (new Api())->getDevPosts();
            $this->cache->set(__METHOD__, $data, (60*60));
        }

        return $this->json($data);
    }

    /**
     * @Route("/lodestone/feasts")
     * @Route("/Lodestone/Feasts")
     */
    public function lodestoneFeats(Request $request)
    {
        $this->appManager->fetch($request);

        return $this->json((new Api())->getFeast(
            $request->get('season'),
            $request->request->all()
        ));
    }

    /**
     * @Route("/lodestone/deepdungeon")
     * @Route("/Lodestone/DeepDungeon")
     */
    public function lodestoneDeepDungeon(Request $request)
    {
        $this->appManager->fetch($request);

        return $this->json((new Api())->getDeepDungeon(
            $request->request->all()
        ));
    }
}
