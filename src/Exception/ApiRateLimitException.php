<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiRateLimitException extends HttpException
{
    const CODE = 429;
}
