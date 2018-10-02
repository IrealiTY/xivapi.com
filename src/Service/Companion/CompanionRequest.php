<?php

namespace App\Service\Companion;

class CompanionRequest
{
    public $baseUri;
    public $apiRoute;
    public $json = [];
    public $headers = [];

    public function __construct(
        string $baseUri,
        string $apiRoute,
        array $json = [],
        bool $token = true
    ) {
        // append on sight version
        $this->baseUri  = $baseUri;
        $this->apiRoute = Companion::VERSION_PATH . $apiRoute;
        $this->json     = $json;

        // build headers
        $this->headers = [
            'request-id'    => $apiRoute, // "api-api-api-api", //\Ramsey\Uuid\Uuid::uuid4()->toString(),
            'Content-Type'  => 'application/json;charset=utf-8',
            'Accept'        => '*/*',
            'domain-type'   => 'global',
            'User-Agent'    => 'ffxivcomapp-e/1.0.0.5 CFNetwork/902.2 Darwin/17.7.0',
        ];

        // todo - this needs automating and storing
        if ($token) {
            $this->headers['token'] = Companion::getToken();
        }
    }
}
