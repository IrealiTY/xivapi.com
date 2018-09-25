<?php

namespace App\EventListener;

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
        
            // handle language
            if (is_array($json) || is_object($json)) {
                $json = $this->handleLanguage($request, $json);
            }

            // if its a list, handle columns per entry
            // ignored when schema is requested
            if (!$event->getRequest()->get('schema')) {
                if (isset($json['Pagination']) && $json['Results']) {
                    foreach ($json['Results'] as $r => $result) {
                        $json['Results'][$r]= $this->handleColumns($request, $result);
                    }
                } else {
                    $json = $this->handleColumns($request, $json);
                }
            }
            
            // last minute handlers
            if (is_array($json) || is_object($json)) {
                $json = $this->handleMini($request, $json);
                $json = $this->handleDataTypes($json);
                $json = $this->handleColumnSorting($json);
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
    
    /**
     * Handle data type, this is mostly to fix large ints
     */
    public function handleDataTypes($data)
    {
        foreach ($data as $i => $value) {
            if (is_array($value)) {
                $data[$i] = $this->handleDataTypes($value);
            } else {
                if (count(explode('.', $value)) > 1) {
                    $data[$i] = (string)trim($value);
                } else if (is_numeric($value)) {
                    $data[$i] = strlen($value) >= 10 ? (string)trim($value) : (int)intval(trim($value));
                } else if ($value === true || $value === false) {
                    $data[$i] = (bool)$value;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Provide universal translated fields, for example:
     * - "Name_fr" becomes "Name" in french
     */
    public function handleLanguage(Request $request, $data)
    {
        return Language::handle($data, $request->get('language'));
    }
    
    /**
     * Handles any custom columns the user needs, this should
     * check the users API Key
     */
    public function handleColumns(Request $request, $data)
    {
        if ($columns = $request->get('columns')) {
            $columns = array_unique(explode(',', $columns));
            $columns = $this->detectRangeColumns($columns);
            
            // empty?
            if (!$columns) {
                return $data;
            }
            
            $newData = [];
            foreach ($columns as $col) {
                $newData[$col] = $this->getArrayValue($data, $col);
            }
            
            foreach ($newData as $index => $value) {
                $dotCount = count(explode('.', $index));
                
                if ($dotCount > 10) {
                    throw new \Exception("What possible data is in 10 nested arrays?");
                }
                
                if ($dotCount > 1) {
                    $this->handleDotNotationToArray($newData, $index, $value);
                    unset($newData[$index]);
                }
            }
            
            return $newData;
        }
        
        return $data;
    }
    
    /**
     * Handle any minimisation
     */
    public function handleMini(Request $request, $data)
    {
        if ($request->get('minify')) {
            $data = ContentMinified::mini($data);
        }
        
        return $data;
    }
    
    /**
     * Convert dot notations into arrays
     */
    public function handleDotNotationToArray(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
        
        return $array;
    }
    
    /**
     * Format columns so lengthy ones can be used in series
     */
    public function detectRangeColumns($columns)
    {
        // reformat some keys
        foreach ($columns as $i => $column) {
            $column = explode('.', $column);
            
            $countColumn = false;
            foreach ($column as $j => $col) {
                if (substr($col, 0, 1) === '*') {
                    // remove this column as it will be merged later
                    unset($columns[$i]);
                    
                    // grab column count
                    $countColumn = (int)substr($col, 1);
                    break;
                }
            }
            
            // Append all count columns
            if ($countColumn) {
                // build a bunch of columns
                foreach (range(0, $countColumn) as $r) {
                    $columns[] = implode(
                        '.', str_ireplace("*{$countColumn}", $r, $column)
                    );
                }
            }
        }
        
        return $columns;
    }
    
    /**
     * Allows you to access a multi-dimensional array using dot notation, eg:
     * [x][y][z] = x.y.z
     */
    public function getArrayValue($array, $key, $default = null)
    {
        $value = $default;
        if (is_array($array) && array_key_exists($key, $array)) {
            $value = $array[$key];
        } else if (is_object($array) && property_exists($array, $key)) {
            $value = $array->$key;
        } else {
            $segments = explode('.', $key);
            
            foreach ($segments as $segment) {
                if (is_array($array) && array_key_exists($segment, $array)) {
                    $value = $array = $array[$segment];
                } else if (is_object($array) && property_exists($array, $segment)) {
                    $value = $array = $array->$segment;
                } else {
                    $value = $default;
                    break;
                }
            }
        }
        
        return $value;
    }

    /**
     * Recursively ksort an array
     */
    public function handleColumnSorting($array)
    {
        foreach ($array as $i => $value) {
            if (is_array($value)) {
                $array[$i] = $this->handleColumnSorting($value);
            }
        }

        ksort($array);
        return $array;
    }
}
