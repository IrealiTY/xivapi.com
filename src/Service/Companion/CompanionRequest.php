<?php

namespace App\Service\Companion;

class CompanionRequest
{
    public $method;
    public $baseUri;
    public $apiRoute;
    public $json = [];
    public $headers = [];

    public function __construct(
        string $method = '',
        string $baseUri = '',
        string $apiRoute = '',
        array $json = [],
        bool $token = true
    ) {
        // append on sight version
        $this->method   = $method;
        $this->baseUri  = $baseUri;
        $this->apiRoute = Companion::VERSION_PATH . $apiRoute;
        $this->json     = $json;

        // build headers
        $this->headers = [
            'request-id'    => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'Content-Type'  => 'application/json;charset=utf-8',
            'User-Agent'    => 'ffxivcomapp-e/1.0.1.0 CFNetwork/974.2.1 Darwin/18.0.0',
        ];

        if ($token) {
            $this->headers['token'] = Companion::getToken()->token;
        }
    }
    
    public function getMethod(): string
    {
        return strtolower($this->method);
    }
    
    public function setMethod(string $method)
    {
        $this->method = $method;
        return $this;
    }
    
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
    
    public function setBaseUri(string $baseUri)
    {
        $this->baseUri = $baseUri;
        return $this;
    }
    
    public function getApiRoute(): string
    {
        return $this->apiRoute;
    }
    
    public function setApiRoute(string $apiRoute)
    {
        $this->apiRoute = $apiRoute;
        return $this;
    }
    
    public function getJson(): array
    {
        return $this->json;
    }
    
    public function setJson(array $json)
    {
        $this->json = $json;
        return $this;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }
}
