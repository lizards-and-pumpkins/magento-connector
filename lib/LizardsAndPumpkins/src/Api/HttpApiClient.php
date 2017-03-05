<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

interface HttpApiClient
{
    /**
     * Headers array format:
     * [
     *    'Header-Name' => 'Header Value'
     * ]
     * 
     * @param string $url
     * @param string $body
     * @param string[] $headers
     * @return string
     * @throws \LizardsAndPumpkins\MagentoConnector\Api\InvalidUrlException
     */
    public function putRequest(string $url, string $body, array $headers): string;

    /**
     * Headers array format:
     * [
     *    'Header-Name' => 'Header Value'
     * ]
     * 
     * @param string $url
     * @param string[] $headers
     * @return string
     * @throws \LizardsAndPumpkins\MagentoConnector\Api\InvalidUrlException
     */
    public function getRequest(string $url, array $headers): string;
}
