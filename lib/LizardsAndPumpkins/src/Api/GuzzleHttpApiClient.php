<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;

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

        $this->validateStatusCode($response);

        return (string)$response->getBody();
    }

    public function getRequest(string $url, string $body, array $headers): string
    {
        $this->validateUrl($url);
        $method = 'GET';
        $response = $this->sendRequest($url, $method, $body, $headers);

        $this->validateStatusCode($response);

        return (string)$response->getBody();
    }

    private function validateUrl(string $url)
    {
        if ('' === $url) {
            throw new InvalidHostException('URL must not be empty.');
        }

        $parts = parse_url($url);
        if (false === $parts) {
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

    /**
     * @param Psr7Response $response
     */
    private function validateStatusCode($response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RequestFailedException(
                sprintf(
                    'The HTTP response status code of the API is not within the expected 200-299 range, got %d',
                    $statusCode
                )
            );
        }
    }
}
