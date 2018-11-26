<?php

namespace App\Service\Apps;

use App\Entity\App;
use App\Exception\ApiRateLimitException;
use App\Exception\UnauthorizedAccessException;
use App\Service\Redis\Cache;
use App\Service\User\Time;
use App\Service\User\UserService;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Cast\Object_;
use Symfony\Component\HttpFoundation\Request;

class AppManager
{
    const CACHE_RATE_LIMIT   = 'app_rate_limit_{app}_{time}_{ip}';
    const CACHE_HITS_TOTAL   = 'app_hits_total_{app}';
    const CACHE_HITS_LIMITED = 'app_hits_limited_{app}';
    const CACHE_HISTORY      = 'app_hits_history_{app}';
    const CACHE_TOKEN        = 'app_hits_tags_{app}_{tag}_{time}';
    const CACHE_HISTORY_TIME = (60 * 60 * 24 * 30); // 30 days
    const MAX_HISTORY_TIME   = 360;

    /** @var EntityManagerInterface $em */
    private $em;
    /** @var UserService $userService */
    private $userService;
    /** @var Cache */
    private $cache;

    public function __construct(EntityManagerInterface $em, UserService $userService, Cache $cache)
    {
        $this->em = $em;
        $this->userService = $userService;
        $this->cache = $cache;
    }

    /**
     * Fetch an API app from the request, if $keyRequired is set then
     * an exception is thrown if no key is provided (eg the endpoint
     * requires a key to be accessed)
     */
    public function fetch(Request $request, $keyRequired = false)
    {
        // attempt to fetch users app
        $key  = $request->get('key');
        $repo = $this->em->getRepository(App::class);

        // use fetched key otherwise use default
        /** @var App $app */
        $app  = $repo->findOneBy([ 'apiKey' => $key ]) ?: $this->getDefaultKey();
        if ($keyRequired && $app->isDefault() && getenv('APP_ENV') === 'prod') {
            throw new UnauthorizedAccessException();
        }

        if ($app->getUser()) {
            $app->getUser()->checkBannedStatus();
        }

        //
        // rate limit check
        // - this also applies to default apps
        //

        $a = [
            '{app}'  => $app->getApiKey(),
            '{time}' => time(),
            '{ip}'   => md5($request->getClientIp())
        ];

        $keys = (Object)[
            'CACHE_RATE_LIMIT'   => str_ireplace(array_keys($a), $a, self::CACHE_RATE_LIMIT),
            'CACHE_HITS_TOTAL'   => str_ireplace(array_keys($a), $a, self::CACHE_HITS_TOTAL),
            'CACHE_HITS_LIMITED' => str_ireplace(array_keys($a), $a, self::CACHE_HITS_LIMITED),
            'CACHE_HISTORY'      => str_ireplace(array_keys($a), $a, self::CACHE_HISTORY),
        ];

        $this->cache->increment($keys->CACHE_HITS_TOTAL);
        $this->cache->increment($keys->CACHE_RATE_LIMIT);

        if (getenv('APP_ENV') === 'prod' && $this->cache->getCount($keys->CACHE_RATE_LIMIT) > $app->getApiRateLimit()) {
            $this->cache->increment($keys->CACHE_HITS_LIMITED);
            throw new ApiRateLimitException(
                ApiRateLimitException::CODE,
                'App receiving too many requests from this IP'
            );
        }

        // if default, return it
        if ($app->isDefault()) {
            return $app;
        }

        //
        // Tags
        //
        if ($tags = $request->get('tags')) {
            $tags = preg_replace("/[^a-zA-Z0-9,-_]+/i", "", $tags);
            $tags = explode(',', $tags);

            // increment tags
            foreach ($tags as $tag) {
                $this->cache->increment(
                    str_ireplace(
                        ['{app}', '{tag}', '{time}'],
                        [$app->getApiKey(), $tag, time()],
                        self::CACHE_TOKEN
                    )
                );
            }
        }

        //
        // record history
        //
        $event = [
            time(),
            substr(round(microtime(true) * 1000), 0 -3),
            $request->getMethod(),
            $request->get('language') ?? 'en',
            $request->getPathInfo()
        ];

        $history = (array)$this->cache->get($keys->CACHE_HISTORY) ?? [];
        $history[] = $event;
        $history = array_values(array_filter($history));
        
        $this->cache->set($keys->CACHE_HISTORY, $history, self::CACHE_HISTORY_TIME);
        return $app;
    }

    /**
     * Get stats for a dev key
     */
    public function getStats(App $app): array
    {
        $a = [
            '{app}' => $app->getApiKey(),
        ];

        $keys = (Object)[
            'CACHE_RATE_LIMIT'   => str_ireplace(array_keys($a), $a, self::CACHE_RATE_LIMIT),
            'CACHE_HITS_TOTAL'   => str_ireplace(array_keys($a), $a, self::CACHE_HITS_TOTAL),
            'CACHE_HITS_LIMITED' => str_ireplace(array_keys($a), $a, self::CACHE_HITS_LIMITED),
            'CACHE_HISTORY'      => str_ireplace(array_keys($a), $a, self::CACHE_HISTORY),
        ];

        $history = $this->cache->get($keys->CACHE_HISTORY);
        $history = $history ? array_reverse((array)$history) : [];
        
        foreach ($history as $i => $h) {
            $timestamp = Carbon::createFromTimestampUTC($h[0]);
            $timestamp->setTimezone(Time::get());
            $history[$i] = (Object)[
                'Time'      => $timestamp->format('Y-m-d H:i:s'),
                'Unix'      => strtotime($timestamp->format('Y-m-d H:i:s')),
                'Minute'    => $timestamp->format('H:i'),
                'MS'        => $h[1],
                'Method'    => $h[2],
                'Language'  => $h[3],
                'Endpoint'  => $h[4],
            ];
        }

        //
        // Requests per second
        //

        $reqPerSec    = [];
        $reqPerMinute = [];
        $chartData    = [];
        $historyTable = [];
        
        // add 1000 events
        foreach ($history as $h) {
            if (count($historyTable) < 1000) {
                $historyTable[] = $h;
                continue;
            }
            
            break;
        }
        
        if ($historyTable) {
            foreach ($historyTable as $row) {
                $reqPerSec[$row->Unix] = isset($reqPerSec[$row->Unix]) ? $reqPerSec[$row->Unix] + 1 : 1;
            }
    
            $reqPerSec = round(array_sum($reqPerSec) / count($reqPerSec), 3);
    
            //
            // Requests per minute
            //
            foreach ($history as $row) {
                $reqPerMinute[$row->Minute] = isset($reqPerMinute[$row->Minute]) ? $reqPerMinute[$row->Minute] + 1 : 1;
        
                if (count($reqPerMinute) > self::MAX_HISTORY_TIME) {
                    break;
                }
            }
            
            //
            // Chart!
            //
            foreach (range(0, self::MAX_HISTORY_TIME) as $i) {
                $timestamp = Carbon::now(Time::get())->subMinutes($i)->format('H:i');
                $chartData[$timestamp] = $reqPerMinute[$timestamp] ?? 0;
            }
    
            // flip so "latest" is to the right
            $chartData = array_reverse($chartData);
        }

        //
        // Tags
        //
        if ($tags = $this->cache->keys('app_hits_tags_'. $app->getApiKey() .'_*')) {
            $tagsData = [];

            foreach ($tags as $tag) {
                $count = $this->cache->getCount($tag);
                [$a, $b, $c, $key, $tag, $time] = explode('_', $tag);
                $tagsData[$tag] = isset($tagsData[$tag]) ? $tagsData[$tag] + $count : $count;
            }
        }
        
        return [
            'CACHE_HITS_TOTAL'   => $this->cache->getCount($keys->CACHE_HITS_TOTAL),
            'CACHE_HITS_LIMITED' => $this->cache->getCount($keys->CACHE_HITS_LIMITED),
            'HISTORY_TABLE'      => $historyTable,
            'HISTORY'            => $history,
            'REQ_PER_SEC'        => $reqPerSec,
            'CHART'              => $chartData,
            'CHART_MAX'          => $chartData ? max($chartData) * 1.50 : 10,
            'CHART_LENGTH_HRS'   => self::MAX_HISTORY_TIME / 60,
            'TAGS'               => $tagsData ?? false,
        ];
    }

    /**
     * Return the default API key
     */
    public function getDefaultKey(): App
    {
        return (new App())->setDefault(true);
    }

    /**
     * Fetch an App via its ID
     */
    public function get(string $id)
    {
        $repo = $this->em->getRepository(App::class);
        return $repo->findOneBy([ 'id' => $id ]);
    }

    /**
     * Create a new App
     */
    public function create()
    {
        $user = $this->userService->getUser();

        if (!$user) {
            throw new \Exception('Not logged in');
        }

        $app = new App();
        $app->setUser($user)
            ->setName('App #'. (count($user->getApps()) + 1))
            ->setLevel(2)
            ->setApiRateLimit(App::RATE_LIMITS[2]);

        $this->em->persist($app);
        $this->em->flush();
        return $app;
    }
}
