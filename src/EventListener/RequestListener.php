<?php

namespace App\EventListener;

use App\Service\Common\Environment;
use App\Service\Common\Language;
use App\Service\Common\Maintenance;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        /** @var Request $request */
        $request = $event->getRequest();

        Environment::set($request);
        Environment::ensureValidHost($request);
        Maintenance::check($request);
        Language::set($request);
    }
}
