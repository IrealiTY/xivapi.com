<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiRestrictedException extends HttpException
{
    const CODE = 429;
    const MESSAGE = 'A valid API key that has access to this endpoint is required.';
}
