<?php

namespace App\EventListener;

use App\Service\Apps\AppManager;
use App\Service\Apps\AppRequest;
use App\Service\Common\Environment;
use App\Service\Common\Language;
use App\Service\ThirdParty\GoogleAnalytics;
use App\Service\ThirdParty\Sentry;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /** @var UserService */
    private $userService;
    /** @var AppManager */
    private $appManager;

    public function __construct(AppManager $appManager, UserService $userService)
    {
        $this->appManager = $appManager;
        $this->userService = $userService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        Sentry::install();

        /** @var Request $request */
        $request = $event->getRequest();

        // Quick hack to allow json body requests
        if ($json = $request->getContent()) {
            if (trim($json[0]) === '{') {
                $json = \GuzzleHttp\json_decode($json);

                foreach($json as $key => $value) {
                    $request->request->set($key, $value);
                }
            }
        }

        // register environment
        Environment::register($request);

        // register language based on domain
        Language::register($request);

        // record analytics
        GoogleAnalytics::hit(getenv('GOOGLE_ANALYTICS'), $request->getPathInfo());
    
        // register app keys
        AppRequest::setManager($this->appManager);
        AppRequest::setUser($this->userService->getUser());
        AppRequest::handleAppRequestRegistration($request);
        AppRequest::handleTracking($request);
        AppRequest::handleRateLimit($request);
    }
}
