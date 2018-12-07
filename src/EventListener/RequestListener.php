<?php

namespace App\EventListener;

use App\Service\Apps\AppManager;
use App\Service\Common\Environment;
use App\Service\Common\GoogleAnalytics;
use App\Service\Common\Language;
use App\Service\Common\Maintenance;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /** @var AppManager */
    private $apps;

    public function __construct(AppManager $appManager)
    {
        $this->apps = $appManager;
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
        
        Environment::set($request);
        Environment::ensureValidHost($request);
        Language::set($request);
        $this->apps->track($request);
    }
}
