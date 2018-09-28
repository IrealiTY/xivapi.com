<?php

namespace App\Service\Common;

use Symfony\Component\HttpFoundation\Request;

/**
 * Set application environment based on url
 */
class Environment
{
    const CONSTANT = 'ENVIRONMENT';

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
}
