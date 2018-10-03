<?php

namespace App\Service\Common;

use Symfony\Component\HttpFoundation\Request;

/**
 * Handle application environments
 */
class Environment
{
    const CONSTANT = 'ENVIRONMENT';

    // set environment
    public static function set(Request $request)
    {
        $environment = 'prod';

        $host = $request->getHost();
        $host = explode('.', $host);

        if ($host[0] === 'staging') {
            $environment = 'staging';
        }

        if ($host[1] === 'local') {
            $environment = 'local';
        }

        if (!defined(self::CONSTANT)) {
            define(self::CONSTANT, $environment);
        }
    }

    /**
     * Checks the request came from a valid host, this restricts
     * '/japan/xxx' endpoints to 'lodestone.xivapi.com'
     */
    public static function ensureValidHost(Request $request)
    {
        $path = explode('/', $request->getPathInfo());
        if ($request->getHost() == 'lodestone.xivapi.com' && $path[1] !== 'japan') {
            die('not allowed');
        }
    }
}
