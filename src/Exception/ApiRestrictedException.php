<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiRestrictedException extends HttpException
{
    const CODE    = 429;
    const MESSAGE = 'A valid API key that has access to this endpoint is required.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
