<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class GuzzleAdapter implements HttpApiClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function putRequest(string $url, string $body, array $headers): string
    {
        $this->validateUrl($url);
        return (string)$this->client->send(new Request('PUT', $url, $headers, $body))->getBody();
    }

    public function getRequest(string $url, string $body, array $headers): string
    {
        $this->validateUrl($url);
        return (string)$this->client->send(new Request('GET', $url, $headers, $body))->getBody();
    }

    private function validateUrl(string $url)
    {
        if ($url === '') {
            throw new InvalidHostException('Url must not be empty.');
        }
    }
}
