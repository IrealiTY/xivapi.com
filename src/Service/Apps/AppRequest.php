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
use App\Exception\ApiRateLimitException;
use App\Exception\ApiRestrictedException;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\GoogleAnalytics;
use Symfony\Component\HttpFoundation\Request;

class AppRequest
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
    /** @var App */
    private static $app = null;

    /**
     * @param AppManager $manager
     */
    public static function setManager(AppManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * Get the current registered app
     *
     * @return App|null
     */
    private static function app(): ?App
    {
        $app = self::$app;

        // Ignore if no app is provided
        if ($app == false) {
            return null;
        }

        return $app;
    }

    /**
     * Register an application
     *
     * @param Request $request
     */
    public static function handleAppRequestRegistration(Request $request): void
    {
        // grab controller related to this API request
        $controller = explode('::', $request->attributes->get('_controller'))[0];

        /** @var App $app */
        $app = $request->get('key') ? self::$manager->getByKey($request->get('key')) : false;
        
        // check if app can access this endpoint
        if (in_array($controller, self::URL) && getenv('IS_LOCAL') == '1') {
            if ($app == false) {
                throw new ApiRestrictedException();
            }
        }

        // Do nothing if no app has been found (likely not using API)
        if ($app === false) {
            return;
        }

        self::$app = $app;

        // Track Developer App on Google Analytics (this is for XIVAPI Analytics)
        GoogleAnalytics::event(
            GoogleAnalytics::XIVAPI_ID,
            'Apps',
            $app->getApiKey(),
            "{$app->getName()} - {$app->getUser()->getUsername()}"
        );
    }

    /**
     * Track app requests using Google Analytics
     *
     * @param Request $request
     */
    public static function handleTracking(Request $request)
    {
        if ($app = self::app()) {
            // If the app has Google Analytics, send a hit request.
            if ($app->hasGoogleAnalytics()) {
                GoogleAnalytics::hit(
                    $app->getGoogleAnalyticsId(),
                    $request->getPathInfo()
                );
            }
        }
    }

    /**
     * Handle an apps rate limit
     *
     * @param Request $request
     */
    public static function handleRateLimit(Request $request)
    {
        if ($app = self::app()) {
            $ip = md5($request->getClientIp());
            $key = "app_rate_limit_ip_{$ip}_{$app->getApiKey()}";

            // increment any counts
            $count = Redis::Cache()->get($key);
            $count = $count ? $count + 1 : 1;
            Redis::Cache()->set($key, $count, 1);

            // check limit against this ip
            if ($count > $app->getApiRateLimit()) {
                // if the app has Google Analytics, send an event
                if ($app->hasGoogleAnalytics()) {
                    GoogleAnalytics::event(
                        $app->getGoogleAnalyticsId(),
                        'Exceptions',
                        'ApiRateLimitException',
                        $ip
                    );
                }

                throw new ApiRateLimitException();
            }
        }
    }

    /**
     * Handle an API exception
     *
     * @param array $json
     */
    public static function handleException(array $json)
    {
        if ($app = self::app()) {
            // if the app has Google Analytics, send an event
            if ($app->hasGoogleAnalytics()) {
                GoogleAnalytics::event(
                    $app->getGoogleAnalyticsId(),
                    'Exceptions',
                    'ApiServiceErrorException',
                    $json['Message']
                );

                GoogleAnalytics::event(
                    $app->getGoogleAnalyticsId(),
                    'Exceptions',
                    'ApiServiceCodeException',
                    $json['Debug']['Code']
                );
            }
        }
    }
}
