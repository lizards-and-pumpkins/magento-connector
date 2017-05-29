<?php

namespace LizardsAndPumpkins\MagentoConnector\Api;

class PhpStreamHttpApiClient implements HttpApiClient
{
    /**
     * @param string $url
     * @param string $body
     * @param string[] $headers
     * @return string
     */
    public function doPutRequest($url, $body, array $headers)
    {
        $httpRequestContext = stream_context_create(['http' => $this->buildStreamContextOptions([
            'method'  => 'PUT',
            'header'  => $this->concatHeaderNamesAndValues($headers),
            'content' => $body,
        ])]);
        return $this->doHttpRequest($url, $httpRequestContext);
    }

    /**
     * @param string $url
     * @param string[] $headers
     * @return string
     */
    public function doGetRequest($url, array $headers)
    {
        $httpRequestContext = stream_context_create(['http' => $this->buildStreamContextOptions([
            'method' => 'GET',
            'header' => $this->concatHeaderNamesAndValues($headers),
        ])]);
        return $this->doHttpRequest($url, $httpRequestContext);
    }

    /**
     * @param string $url
     * @param resource $httpRequestContext
     * @return string
     */
    private function doHttpRequest($url, $httpRequestContext)
    {
        $this->validateUrl($url);
        // The variable $http_response_header is set by PHP when using HTTP stream wrappers.
        // See http://php.net/manual/en/reserved.variables.httpresponseheader.php for details.
        $http_response_header = null;
        $responseBody = file_get_contents($url, false, $httpRequestContext);
        $this->validateHttpResponse($http_response_header);

        return (string) $responseBody;
    }

    /**
     * @param string $url
     */
    private function validateUrl($url)
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

    /**
     * @param string[] $httpResponseHeaders
     * @return int
     */
    private function parseResponseStatusCode(array $httpResponseHeaders)
    {
        foreach ($httpResponseHeaders as $header) {
            if (preg_match('#HTTP/\S+ (?<responseCode>\d+)#', $header, $matches)) {
                return (int) $matches['responseCode'];
            }
        }

        return 0;
    }

    private function validateHttpResponse(array $phpResponseHeaders = null)
    {
        $httpResponseStatusCode = $this->parseResponseStatusCode($this->getRawResponseHeaders($phpResponseHeaders));
        if ($httpResponseStatusCode < 200 || $httpResponseStatusCode >= 208) {
            $message = sprintf(
                'The HTTP response status code of the Lizards & Pumpkins API ' .
                'is not within the expected 200-207 range, got %d', $httpResponseStatusCode
            );
            throw new RequestFailedException($message);
        }
    }

    /**
     * @param string[] $headerNameToValueMap
     * @return string[]
     */
    private function concatHeaderNamesAndValues(array $headerNameToValueMap)
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
    protected function getRawResponseHeaders(array $httpResponseHeaders = null)
    {
        return (array) $httpResponseHeaders;
    }

    /**
     * @param mixed[] $streamContextOptions
     * @return mixed[]
     */
    protected function buildStreamContextOptions(array $streamContextOptions)
    {
        return $streamContextOptions;
    }
}
