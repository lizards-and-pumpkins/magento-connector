<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

class PhpStreamHttpApiClient implements HttpApiClient
{
    public function putRequest(string $url, string $body, array $headers): string
    {
        $httpRequestContext = stream_context_create(['http' => [
            'method' => 'PUT',
            'header' => $this->concatHeaderNamesAndValues($headers),
            'content' => $body,
        ]]);
        return $this->doHttpRequest($url, $httpRequestContext);
    }

    public function getRequest(string $url, array $headers): string
    {
        $httpRequestContext = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => $this->concatHeaderNamesAndValues($headers),
        ]]);
        return $this->doHttpRequest($url, $httpRequestContext);
    }

    private function doHttpRequest(string $url, $httpRequestContext): string
    {
        $this->validateUrl($url);
        $http_response_header = null;
        $responseBody = file_get_contents($url, false, $httpRequestContext);
        $this->validateHttpResponse($http_response_header);

        return (string) $responseBody;
    }

    private function validateUrl(string $url)
    {
        if ('' === $url) {
            throw new InvalidUrlException('Lizards & Pumpkins API URL must not be empty.');
        }

        $parts = parse_url($url);
        if (false === $parts) {
            throw new InvalidUrlException(sprintf('Unable to parse Lizards & Pumpkins API URL "%s".', $url));
        }

        if (empty($parts['host'])) {
            throw new InvalidUrlException(sprintf('Unable to parse host from Lizards & Pumpkins API URL "%s".', $url));
        }

        if ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https') {
            $message = sprintf('Lizards & Pumpkins API URL must start with "http" or "https", got "%s".', $url);
            throw new InvalidUrlException($message);
        }
    }

    private function parseResponseStatusCode(array $httpResponseHeaders): int
    {
        foreach ($httpResponseHeaders as $header) {
            if (preg_match('#HTTP/\S+ (?<responseCode>\d+)#', $header, $matches)) {
                return (int) $matches['responseCode'];
            }
        }

        return 0;
    }

    private function validateHttpResponse(array $http_response_header = null)
    {
        $httpResponseStatusCode = $this->parseResponseStatusCode($this->getRawResponseHeaders($http_response_header));
        if ($httpResponseStatusCode < 200 || $httpResponseStatusCode >= 300) {
            $message = sprintf(
                'The HTTP response status code of the Lizards & Pumpkins API ' .
                'is not within the expected 200-299 range, got %d', $httpResponseStatusCode
            );
            throw new RequestFailedException($message);
        }
    }

    /**
     * @param string[] $headerNameToValueMap
     * @return string[]
     */
    private function concatHeaderNamesAndValues(array $headerNameToValueMap): array
    {
        return array_map(function ($headerName, $headerValue) {
            return $headerName . ': ' . $headerValue;
        }, array_keys($headerNameToValueMap), array_values($headerNameToValueMap));
    }

    /**
     * Tests can override this method to provide response headers.
     *
     * @param string[]|null $httpResponseHeaders
     * @return string[]
     */
    protected function getRawResponseHeaders(array $httpResponseHeaders = null): array
    {
        return (array) $httpResponseHeaders;
    }
}
