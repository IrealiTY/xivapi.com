<?php

namespace App\Controller;

use App\Entity\MapPosition;
use App\Entity\MemoryData;
use App\Service\Apps\AppManager;
use App\Service\Content\ContentList;
use App\Service\Content\GameServers;
use App\Service\Data\CsvReader;
use App\Service\GamePatch\Patch;
use App\Service\Common\GoogleAnalytics;
use App\Service\Redis\Cache;
use App\Utils\ContentNameCaseConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class XivGameContentController extends Controller
{
    /** @var Cache */
    private $cache;
    /** @var ContentList */
    private $contentList;
    /** @var AppManager */
    private $appManager;
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        Cache $cache,
        ContentList $contentList,
        AppManager $appManager,
        EntityManagerInterface $em
    ) {
        $this->cache = $cache;
        $this->contentList = $contentList;
        $this->appManager = $appManager;
        $this->em = $em;
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
     * @Route("/MapData/Download")
     * @Route("/mapdata/download")
     */
    public function mapDataDownload(Request $request)
    {
        $repo    = $this->em->getRepository(MapPosition::class);
        $headers = null;
        
        /** @var MapPosition $pos */
        $fp = fopen(__DIR__.'/MapData.csv', 'w');
        foreach ($repo->findAll() as $pos) {
            if (!$headers) {
                $headers = array_keys($pos->toArray());
                fputcsv($fp, $headers);
            }
    
            fputcsv($fp, array_values($pos->toArray()));
        }
    
        fclose($fp);
    
        return $this->file(
            new File(__DIR__.'/MapData.csv')
        );
    }
    
    /**
     * @Route("/MemoryData/Download")
     * @Route("/memorydata/download")
     */
    public function memoryDataDownload(Request $request)
    {
        $repo    = $this->em->getRepository(MemoryData::class);
        $headers = null;
        
        /** @var MemoryData $pos */
        $fp = fopen(__DIR__.'/MemoryData.csv', 'w');
        foreach ($repo->findAll() as $obj) {
            if (!$headers) {
                $headers = array_keys($obj->toArray());
                fputcsv($fp, $headers);
            }
            
            fputcsv($fp, array_values($obj->toArray()));
        }
        
        fclose($fp);
        
        return $this->file(
            new File(__DIR__.'/MemoryData.csv')
        );
    }
    
    /**
     * @Route("/MapData/{name}/{id}")
     * @Route("/mapdata/{name}/{id}")
     */
    public function mapData(Request $request, $name, $id)
    {
        $name = strtolower($name);
        
        $nameToField = [
            'map'       => 'MapID',
            'placename' => 'PlaceNameID',
            'territory' => 'MapTerritoryID',
        ];
        
        $field = $nameToField[$name] ?? false;
        if (!$field) {
            throw new \Exception('There is no map data for the content: '. $name);
        }
        
        $repo = $this->em->getRepository(MapPosition::class);
        $pos  = [];
        
        /** @var MapPosition $position */
        foreach ($repo->findBy([ $field => $id ], [ 'Added' => 'ASC' ]) as $position) {
            $pos[] = $position->toArray();
        }
        
        return $this->json($pos);
    }

    /**
     * todo - deprecate /colors endpoint
     * @Route("/Colors")
     * @Route("/colors")
     * @Route("/misc/colors")
     */
    public function colors()
    {
        $csv    = CsvReader::Get(__DIR__.'/../Service/Helpers/UIColor.csv');
        $colors = [];

        foreach ($csv as $i => $row) {
            // ignore headings
            if ($i < 3) {
                continue;
            }

            [$colourA, $colourB] = $row;

            $colors[] = [
                'ID' => $row['key'],
                'ColorA' => $colourA,
                'ColorB' => $colourB,
                'ColorAHexAlpha' => str_pad(dechex($colourA), 8, '0', STR_PAD_LEFT),
                'ColorBHexAlpha' => str_pad(dechex($colourA), 8, '0', STR_PAD_LEFT),
                'ColorAHex' => substr(str_pad(dechex($colourA), 8, '0', STR_PAD_LEFT), 0, 6),
                'ColorBHex' => substr(str_pad(dechex($colourA), 8, '0', STR_PAD_LEFT), 0, 6),
            ];
        }

        return $this->json($colors);
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
        
        $content = $request->get('schema')
            ? $this->cache->get("schema_{$name}")
            : $this->contentList->get($request, $name, $app);
        
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
    
        $content = $request->get('schema')
            ? $this->cache->get("schema_{$name}")
            : $this->cache->get("xiv_{$name}_{$id}");

        if (!$content) {
            throw new NotFoundHttpException("FFXIV Game Content not found for; ID = {$id}, Name = {$name}");
        }
    
        $duration = microtime(true) - $start;
        GoogleAnalytics::hit([$name, $id]);
        GoogleAnalytics::event('content', 'get', 'duration', $duration);
        return $this->json($content);
    }
}
