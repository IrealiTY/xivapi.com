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
use App\Entity\User;
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
    /** @var User */
    private static $user = null;
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
     * @param User $user
     */
    public static function setUser(User $user): void
    {
        self::$user = $user;
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
        if (self::$user) {
            return;
        }

        // grab controller related to this API request
        $controller = explode('::', $request->attributes->get('_controller'))[0];

        /** @var App $app */
        $app = self::$manager->getByKey($request->get('key') ?: null);
        
        // check if app can access this endpoint
        if (in_array($controller, self::URL)) {
            if (empty($request->get('key')) || $app == null) {
                throw new ApiRestrictedException();
            }
        }

        // Do nothing if no app has been found (likely not using API)
        if ($app == false) {
            return;
        }

        self::$app = $app;

        // Track Developer App on Google Analytics (this is for XIVAPI Analytics)
        GoogleAnalytics::event(
            getenv('GOOGLE_ANALYTICS'),
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
            $ip       = md5($request->getClientIp());
            $keyNow   = "app_rate_limit_ip_{$ip}_{$app->getApiKey()}_now";
            $keyBurst = "app_rate_limit_ip_{$ip}_{$app->getApiKey()}_burst";

            // increment req counts
            $count = Redis::Cache()->get($keyNow);
            $count = $count ? $count + 1 : 1;
            Redis::Cache()->set($keyNow, $count, 1);

            // increment burst
            $burst = Redis::Cache()->get($keyBurst);
            $burst = $burst ? $burst + 1 : 1;
            Redis::Cache()->set($keyBurst, $burst, 5);

            // rate limit is 2x while not in burst timeout
            $burstlimit = 5;
            $ratelimit  = $burst > $burstlimit ? $app->getApiRateLimit() : ($app->getApiRateLimit() * 2);

            // check limit against this ip
            if ($count > $ratelimit && $burst > $burstlimit) {
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
