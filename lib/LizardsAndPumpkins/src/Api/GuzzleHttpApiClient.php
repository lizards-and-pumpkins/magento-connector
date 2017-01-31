<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\Psr7\Request as Psr7Request;

class GuzzleHttpApiClient implements HttpApiClient
{
    /**
     * @var GuzzleClient
     */
    private $client;

    public function __construct(GuzzleClient $client = null)
    {
        $this->client = $client ?: new GuzzleClient();
    }

    public function putRequest(string $url, string $body, array $headers): string
    {
        $this->validateUrl($url);
        $method = 'PUT';
        $response = $this->sendRequest($url, $method, $body, $headers);

        if (((string)$response->getStatusCode())[0] !== '2') {
            throw new RequestFailedException(
                sprintf('Status code %s does not match expected 200.', $response->getStatusCode())
            );
        }

        return (string)$response->getBody();
    }

    public function getRequest(string $url, string $body, array $headers): string
    {
        $this->validateUrl($url);
        $method = 'GET';
        $response = $this->sendRequest($url, $method, $body, $headers);

        if (((string)$response->getStatusCode())[0] !== '2') {
            throw new RequestFailedException(
                sprintf('Status code %s does not match expected 2xx.', $response->getStatusCode())
            );
        }
        return (string)$response->getBody();
    }

    private function validateUrl(string $url)
    {
        if ($url === '') {
            throw new InvalidHostException('URL must not be empty.');
        }

        $parts = parse_url($url);
        if ($parts === false) {
            throw new InvalidHostException('URL seems to be  seriously malformed.');
        }

        if (empty($parts['host'])) {
            throw new InvalidHostException('Host must be specified.');
        }

        if ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https') {
            throw new InvalidHostException('URL must start with either http or https.');
        }
    }

    /**
     * @param string $url
     * @param string $method
     * @param string $body
     * @param array  $headers
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    private function sendRequest(string $url, string $method, string $body, array $headers)
    {
        try {
            $response = $this->client->send(new Psr7Request($method, $url, $headers, $body));
        } catch (GuzzleClientException $e) {
            throw new RequestFailedException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleServerException $e) {
            throw new RequestFailedException($e->getMessage(), $e->getCode(), $e);
        }
        return $response;
    }
}
