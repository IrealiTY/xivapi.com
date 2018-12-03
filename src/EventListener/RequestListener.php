<?php

namespace App\EventListener;

use App\Service\Common\Environment;
use App\Service\Common\Language;
use App\Service\Common\Maintenance;
use App\Service\Common\Statistics;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
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
            $json = \GuzzleHttp\json_decode($json);
            
            foreach($json as $key => $value) {
                $request->request->set($key, $value);
            }
        }
        
        Environment::set($request);
        Environment::ensureValidHost($request);
        Maintenance::check($request);
        Language::set($request);
        Statistics::request($request);
    }
}
