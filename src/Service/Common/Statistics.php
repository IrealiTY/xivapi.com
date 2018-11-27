<?php

namespace App\Service\Common;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class Statistics
{
    const FILENAME          = __DIR__.'/Statistics.php.log';
    const FILENAME_REPORT   = __DIR__.'/Statistics_Report.php.log';
    const DATA              = 'stats_data_%s';
    const DATA_ENDPOINTS    = 'stats_endpoints';
    const DATA_TIMES        = 'stats_response_times';
    const COUNT             = 'stats_count';
    const COUNT_IP          = 'stats_count_ip_%s';
    const COUNT_ENDPOINT    = 'stats_count_endpoint_%s';
    const VALUE_TIMES       = 'stats_response_time';
    
    /** @var \stdClass */
    private static $request;

    /**
     * Get the current built report
     */
    public static function report()
    {
        return json_decode(
            file_get_contents(self::FILENAME_REPORT),
            true
        );
    }

    /**
     * Find entries for a specific IP
     */
    public static function findReportEntriesForKey(string $appKey): array
    {
        $entries = [];
        $data = explode(PHP_EOL, file_get_contents(self::FILENAME));

        foreach ($data as $i => $stat) {
            if (empty($stat)) {
                continue;
            }

            [$time, $micro, $duration, $class, $ip, $key, $uri, $lang] = explode("|", $stat);

            if ($appKey == $key) {
                $time = date('Y-m-d H:i:s', $time);
                $ip = str_pad($ip, 50, ' ');
                $entries[] = "[{$time}] {$ip} {$uri}";
            }
        }

        return $entries;
    }

    /**
     * Build a statistics report
     */
    public static function buildReport()
    {
        $data = explode(PHP_EOL, file_get_contents(self::FILENAME));
        $info = (Object)[
            'total'        => 0,
            'start'        => 0,
            'finish'       => 0,
            'period'       => 0,
            'duration'     => [],
            'ips'          => [],
            'keys'         => [],
            'uri'          => [],
            'lang'         => [],
            'lines'        => [],
            'ip_req_sec'   => [],
        ];
        
        foreach ($data as $stat) {
            if (empty($stat)) {
                continue;
            }
            
            [$time, $micro, $duration, $class, $ip, $key, $uri, $lang] = explode("|", $stat);
            
            // manual modification for market
            $uriData = array_values(array_filter(explode("/", $uri)));
            $uri = "/". ($uriData[0] ?? null);
            
            if ($info->start === 0 || $time < $info->start) {
                $info->start = $time;
            }
    
            if ($info->finish === 0 || $time > $info->finish) {
                $info->finish = $time;
            }
            
            $info->total++;
            $info->duration[]  = $duration;
            $info->ips[$ip]    = (isset($info->ips[$ip])) ? $info->ips[$ip] + 1 : 1;
            $info->keys[$key]  = (isset($info->keys[$key])) ? $info->keys[$key] + 1 : 1;
            $info->uri[$uri]   = (isset($info->uri[$uri])) ? $info->uri[$uri] + 1 : 1;
            $info->lang[$lang] = (isset($info->lang[$lang])) ? $info->lang[$lang] + 1 : 1;
            $info->lines[]     = "[{$time}] {$ip} {$key} - {$uri}";
            
            // Ip requests at a timestamp
            $info->ip_req_sec[$ip][$time] = isset($info->ip_req_sec[$ip][$time]) ? $info->ip_req_sec[$ip][$time] + 1 : 1;
            $info->key_req_sec[$key][$time] = isset($info->ip_req_sec[$key][$time]) ? $info->ip_req_sec[$key][$time] + 1 : 1;
        }
        
        // we don't care about the default key
        unset($info->keys['-']);

        $info->duration = round(array_sum($info->duration) / count($info->duration), 3);
        $info->period = $info->finish - $info->start;
        
        arsort($info->ips);
        arsort($info->keys);
        arsort($info->uri);
        arsort($info->lang);
        
        $info->ips   = array_slice($info->ips, 0, 20);
        $info->keys  = array_slice($info->keys, 0, 20);
        $info->uri   = array_slice($info->uri, 0, 20);
        $info->lines = array_slice(array_reverse($info->lines), 0, 100);
        
        // if someone is "crawling" they're likely to have low durations between each call
        foreach ($info->ip_req_sec as $ip => $times) {
            // 18,000 = 10/req/sec * 30 minutes
            $times = array_slice(array_reverse($times), 0, 18000);
            $info->ip_durations_avg[$ip] = round(array_sum($times) / count($times), 3);
        }
    
        foreach ($info->key_req_sec as $ip => $times) {
            // 18,000 = 10/req/sec * 30 minutes
            $times = array_slice(array_reverse($times), 0, 18000);
            $info->key_durations_avg[$ip] = round(array_sum($times) / count($times), 3);
        }

        file_put_contents(self::FILENAME_REPORT, json_encode($info));

        return $info;
    }

    /**
     * Clean out statistics older than a day
     */
    public static function purgeReport()
    {
        $deadline = (time() - (60*60*24));
        $data     = explode(PHP_EOL, file_get_contents(self::FILENAME));

        foreach ($data as $i => $stat) {
            if (empty($stat)) {
                continue;
            }

            [$time, $micro, $duration, $class, $ip, $key, $uri, $lang] = explode("|", $stat);

            if ($time < $deadline) {
                unset($data[$i]);
                continue;
            }

            break;
        }

        file_put_contents(self::FILENAME, implode(PHP_EOL, $data));
    }

    /**
     * Track a request
     */
    public static function request(Request $request)
    {
        self::setRequest($request);
    }

    /**
     * Track a response
     */
    public static function response(FilterResponseEvent $event)
    {
        // add some info
        self::$request->duration = microtime(true) - self::$request->micro;
        self::$request->class = get_class($event->getResponse());
        
        $data = implode("|", (array)self::$request) . PHP_EOL;
        file_put_contents(self::FILENAME, $data, FILE_APPEND);
    }

    /**
     * todo - record exceptions
     * Track exceptions
     */
    public static function exception(GetResponseForExceptionEvent $event)
    {
    
    }

    /**
     * Set request object
     */
    private static function setRequest(Request $request)
    {
        self::$request = (Object)[
            'time'      => time(),
            'micro'     => microtime(true),
            'duration'  => null,
            'class'     => null,
            'ip'        => $request->getClientIp(),
            'key'       => $request->get('key') ?: '-',
            'uri'       => $request->getPathInfo(),
            'lang'      => Language::current(),
        ];
    }
}
