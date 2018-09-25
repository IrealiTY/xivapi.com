<?php

namespace App\Controller;

use App\Service\Apps\AppManager;
use App\Service\Google\GoogleAnalytics;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchResponse;
use App\Service\Search\Search;
use App\Service\SearchContent\Achievement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\HttpFoundation\Request;

/**
 * @package App\Controller
 */
class SearchController extends Controller
{
    /** @var Search */
    private $search;
    /** @var AppManager */
    private $appManager;

    function __construct(Search $search, AppManager $appManager)
    {
        $this->search = $search;
        $this->appManager = $appManager;
    }

    /**
     * @Route("/Search")
     * @Route("/search")
     * @throws
     */
    public function search(Request $request)
    {
        $start = microtime(true);
        $this->appManager->fetch($request);

        $searchRequest = new SearchRequest();
        $searchRequest->buildFromRequest($request);
        $searchResponse = new SearchResponse($searchRequest);
        $this->search->handleRequest($searchRequest, $searchResponse);
    
        $duration = microtime(true) - $start;
        (new GoogleAnalytics())->hit(['Search'])->event('search', 'get', 'duration', $duration);
        return $this->json($searchResponse->response);
    }
    
    /**
     * @Route("/Search/Schema")
     * @Route("/search/schema")
     */
    public function filters(Request $request)
    {
        $filelist = array_values(array_diff(scandir(__DIR__.'/../Service/SearchContent'), ['..', '.']));
        $schema   = [];
        
        foreach ($filelist as $i => $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
    
            /** @var Achievement $class */
            $classname = substr($file, 0, -4);
            $class = "\\App\\Service\\SearchContent\\{$classname}";
            $schema[$classname] = str_ireplace('%s', '[LANGUAGE]', $class::FIELDS);
        }
    
        (new GoogleAnalytics())->hit(['Search','Schema']);
        return $this->json($schema);
    }
}
