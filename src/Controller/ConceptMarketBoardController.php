<?php

namespace App\Controller;

use App\Service\Apps\AppManager;
use App\Service\Companion\Companion;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\CompanionResponse;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class ConceptMarketBoardController extends Controller
{
    /** @var CompanionMarket */
    private $market;
    /** @var AppManager */
    private $appManager;
    
    public function __construct(CompanionMarket $market, AppManager $appManager)
    {
        $this->market = $market;
        $this->appManager = $appManager;
    }
    
    /**
     * @Route("/Market")
     * @Route("/market")
     */
    public function market(Request $request)
    {
        return $this->render('market/index.html.twig');
    }

    /**
     * @Route("/Market/token")
     * @Route("/market/token")
     */
    public function setToken(Request $request)
    {
        if ($request->get('pass') !== getenv('COMPANION_TOKEN_PASS')) {
            throw new UnauthorizedHttpException('Go away');
        }

        Companion::setToken($request->get('token'));
        return $this->json(1);
    }
    
    /**
     * @Route("/Market/Categories")
     * @Route("/market/categories")
     */
    public function categories(Request $request)
    {
        $app = $this->appManager->fetch($request);

        if ($app->isDefault()) {
            throw new Forbidden403Exception('This route requires an API key');
        }

        return $this->json(
            $this->market->getSearchCategories()
        );
    }

    /**
     * @Route("/Market/{itemId}")
     * @Route("/market/{itemId}")
     */
    public function item(Request $request, int $itemId)
    {
        $app = $this->appManager->fetch($request);

        if ($app->isDefault()) {
            //throw new Forbidden403Exception('This route requires an API key');
        }

        /** @var CompanionResponse $response */
        $response = $this->market->getItemMarketData($itemId);

        if (!$response) {
            throw new NotFoundHttpException('idk what item that is...');
        }

        return $this->render('market/item.html.twig', [
            'item' => $response->response,
        ]);
    }
}
