<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (getenv('IS_LOCAL') || $event->getRequest()->get('debug') == getenv('DEBUG_PASS')) {
            return;
        }
    
        $ex         = $event->getException();
        $path       = $event->getRequest()->getPathInfo();
        $pathinfo   = pathinfo($path);
    
        // record exception
        $message = "{$ex->getMessage()}\n\n{$ex->getTraceAsString()}\n-------\n";
        file_put_contents(__DIR__.'/Exceptions.txt', $message, FILE_APPEND);
        
        if (isset($pathinfo['extension'])) {
            return $event->setResponse(
                new Response("File not found: ". $path, 404)
            );
        }
        
        $file = str_ireplace('/home/dalamud/dalamud', '', $ex->getFile());
        $message = $ex->getMessage() ?: '(no-exception-message)';
        
        $json = [
            'Error'   => true,
            'Message' => "API EXCEPTION: {$message}",
            'Debug' => [
                'Class'   => get_class($ex),
                'File'    => "#{$ex->getLine()} {$file}",
                'Method'  => $event->getRequest()->getMethod(),
                'Path'    => $event->getRequest()->getPathInfo(),
                'Note'    => "Get on discord: https://discord.gg/MFFVHWC and complain to @vekien :)",
                'Code'    => method_exists($ex, 'getStatusCode') ? $ex->getStatusCode() : 500,
            ]
        ];

        $response = new JsonResponse($json, $json['Debug']['Code']);
        $response->headers->set('Content-Type','application/json');
        $response->headers->set('Access-Control-Allow-Origin','*');

        $event->setResponse($response);
    }
}
