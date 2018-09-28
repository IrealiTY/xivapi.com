<?php

namespace App\EventListener;

use App\Service\Common\Arrays;
use App\Service\Common\DataType;
use App\Service\Content\ContentMinified;
use App\Service\Language\Language;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        /** @var JsonResponse $response */
        $response = $event->getResponse();
        /** @var Request $request */
        $request = $event->getRequest();

        // only process if response is a JsonResponse
        if (get_class($response) === JsonResponse::class) {
            // grab json
            $json = json_decode($response->getContent(), true);
            
            // ignore when it's an exception
            if (isset($json['Error']) && isset($json['Debug'])) {
                return;
            }
            
            // last minute handlers
            if (is_array($json)) {
                //
                // Language
                //
                $json = Language::handle($json, $request->get('language'));

                //
                // Schema
                //
                // if its a list, handle columns per entry
                // ignored when schema is requested
                //
                if (!$event->getRequest()->get('schema')) {
                    // get columns param
                    $columns = array_unique(explode(',', $request->get('columns')));

                    if (isset($json['Pagination']) && $json['Results']) {
                        foreach ($json['Results'] as $r => $result) {
                            $json['Results'][$r] = Arrays::extractColumns($result, $columns);
                        }
                    } else {
                        $json = Arrays::extractColumns($json, $columns);
                    }
                }

                //
                // Mini
                //
                if ($request->get('minify')) {
                    $json = ContentMinified::mini($json);
                }

                //
                // Ensure data types are enforced cleanly
                //
                $json = DataType::ensureStrictDataTypes($json);

                //
                // Sort data
                //
                $json = Arrays::sortArrayByKey($json);
            }

            // save
            $response->setContent(
                json_encode($json, JSON_BIGINT_AS_STRING | JSON_PRESERVE_ZERO_FRACTION)
            );
            
            // if pretty printing
            if ($event->getRequest()->get('pretty')) {
                $response->setContent(
                    json_encode(
                        json_decode($response->getContent()), JSON_PRETTY_PRINT
                    )
                );
            }

            $response->headers->set('Content-Type','application/json');
            $response->headers->set('Access-Control-Allow-Origin','*');
            $event->setResponse($response);
        }
    }
}
