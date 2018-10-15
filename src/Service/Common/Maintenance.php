<?php

namespace App\Service\Common;

use App\Exception\MaintenanceException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Maintenance
{
    const FILENAME = __DIR__.'/offline.txt';

    /**
     * Check if the API is in maintenance mode
     */
    public static function check(Request $request): void
    {
        // we don't check for maintenance on the maintenance endpoint
        if ($request->getPathInfo() === '/maintenance') {
            return;
        }

        if (file_exists(self::FILENAME)) {
            throw new MaintenanceException(
                MaintenanceException::CODE,
                file_get_contents(self::FILENAME)
            );
        }
    }

    /**
     * Handle maintenance requests, will enable if "on=some-message"
     * Requires the correct maintenance pass
     */
    public static function handle(Request $request): void
    {
        if ($request->get('pass') !== getenv('MAINTENANCE_PASS')) {
            throw new UnauthorizedHttpException('Go away');
        }

        if ($request->get('on')) {
            file_put_contents(self::FILENAME, trim($request->get('on')));
            return;
        }

        @unlink(self::FILENAME);
    }

}
