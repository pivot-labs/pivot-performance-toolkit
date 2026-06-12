<?php

namespace PerformanceToolkit\Vendor\Psr\Http\Client;

use PerformanceToolkit\Vendor\Psr\Http\Message\RequestInterface;
use PerformanceToolkit\Vendor\Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \PerformanceToolkit\Vendor\Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
