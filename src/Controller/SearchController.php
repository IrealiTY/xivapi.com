<?php

namespace App\Controller;

use App\Service\Apps\AppManager;
use App\Service\Common\GoogleAnalytics;
use App\Service\Search\SearchRequest;
use App\Service\Search\SearchResponse;
use App\Service\Search\Search;
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
        GoogleAnalytics::hit(['Search']);
        GoogleAnalytics::event('search', 'get', 'duration', $duration);
        
        # print_r($searchResponse->response);die;
        
        return $this->json($searchResponse->response);
    }
}
