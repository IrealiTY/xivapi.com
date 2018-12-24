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
use App\Exception\ApiRestrictedException;
use App\Service\Redis\Redis;
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

        // grab key and app
        $app = self::$manager->getByKey($request->get('key'));

        // check if app can access this endpoint
        if (in_array($controller, self::URL) && (empty($key) || $app == false)) {
            throw new ApiRestrictedException(ApiRestrictedException::CODE, ApiRestrictedException::MESSAGE);
        }

        // track app requests
        self::$manager->track($request);

        // (beta) record general statistics
        self::recordGeneralStatistics($request);
    }

    /**
     * Record general statistics for the API key
     *
     * @param Request $request
     */
    private static function recordGeneralStatistics(Request $request): void
    {
        Redis::Cache()->increment("keystats_". $request->get('key'));
        Redis::Cache()->increment("ipstats_". strtoupper(md5($request->getClientIp())));
    }
}
