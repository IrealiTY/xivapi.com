<?php

namespace App\EventListener;

use App\Exception\MaintenanceException;
use App\Service\Common\Environment;
use App\Service\Language\Language;
use App\Service\User\Time;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }


        Environment::set($event->getRequest());

        $path = explode('/', $event->getRequest()->getPathInfo());
        if ($event->getRequest()->getHost() == 'lodestone.xivapi.com' && $path[1] !== 'japan') {
            die('not allowed');
        }
        
        // check for maintenance
        if ($event->getRequest()->getPathInfo() !== '/maintenance' && file_exists(__DIR__.'/../offline.txt')) {
            throw new MaintenanceException(
                MaintenanceException::CODE,
                file_get_contents(__DIR__.'/../offline.txt')
            );
        }

        Language::set($event->getRequest());
    }
}
