<?php

namespace App\Service\Apps;

use App\Controller\CompanionMarketController;
use App\Controller\ConceptTooltipsController;
use App\Controller\LodestoneCharacterController;
use App\Controller\LodestoneController;
use App\Controller\LodestoneFreeCompanyController;
use App\Controller\LodestonePvPTeamController;
use App\Controller\LodestoneStatisticsController;
use App\Controller\SearchController;
use App\Controller\XivGameContentController;
use App\Entity\App;
use App\Exception\ApiRestrictedException;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\GoogleAnalytics;
use Symfony\Component\HttpFoundation\Request;

class Apps
{
    /**
     * List of controllers that require a API Key
     */
    const URL = [
        CompanionMarketController::class,
        ConceptTooltipsController::class,
        LodestoneCharacterController::class,
        LodestoneController::class,
        LodestoneFreeCompanyController::class,
        LodestonePvPTeamController::class,
        LodestoneStatisticsController::class,
        SearchController::class,
        XivGameContentController::class
    ];

    /** @var AppManager */
    private static $manager = null;

    /**
     * @param AppManager $manager
     */
    public static function setManager(AppManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * Register an application
     *
     * @param Request $request
     */
    public static function register(Request $request): void
    {
        // grab controller related to this API request
        $controller = explode('::', $request->attributes->get('_controller'))[0];

        /** @var App $app */
        $app = $request->get('key') ? self::$manager->getByKey($request->get('key')) : false;
        
        // check if app can access this endpoint
        if (in_array($controller, self::URL) && getenv('IS_LOCAL') == '1') {
            if ($app == false) {
                throw new ApiRestrictedException(ApiRestrictedException::CODE, ApiRestrictedException::MESSAGE);
            }
        }

        // Do nothing if no app has been found (likely not using API)
        if ($app === false) {
            return;
        }

        // track app requests
        self::$manager->track($request);

        // Track Developer App on Google Analytics
        GoogleAnalytics::event(
            'Apps',
            $app->getApiKey(),
            "{$app->getName()} - {$app->getUser()->getUsername()}"
        );
    }
}
