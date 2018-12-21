<?php

namespace App\EventListener;

use App\Service\Apps\AppManager;
use App\Service\Common\Environment;
use App\Service\Common\GoogleAnalytics;
use App\Service\Common\Language;
use App\Service\Common\Maintenance;
use App\Service\Redis\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /** @var AppManager */
    private $apps;
    /** @var Cache */
    private $cache;

    public function __construct(AppManager $apps, Cache $cache)
    {
        $this->apps = $apps;
        $this->cache = $cache;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($sentry = getenv('SENTRY')) {
            (new \Raven_Client($sentry))->install();
        }
        

        /** @var Request $request */
        $request = $event->getRequest();
    
        if ($json = $request->getContent()) {
            if (trim($json[0]) === '{') {
                $json = \GuzzleHttp\json_decode($json);
    
                foreach($json as $key => $value) {
                    $request->request->set($key, $value);
                }
            }
        }

        if ($key = $request->get('key')) {
            $key = "keystats_". $key;
            $this->cache->increment($key);

            $ip = "ipstats_". strtoupper(md5($request->getClientIp()));
            $this->cache->increment($ip);
        }
        
        Environment::set($request);
        Environment::ensureValidHost($request);
        Language::set($request);
        $this->apps->track($request);
    }
}
