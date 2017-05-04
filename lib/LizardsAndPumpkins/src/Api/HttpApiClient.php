<?php

declare(strict_types = 1);

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
     */
    public function doPutRequest($url, $body, array $headers);

    /**
     * Headers array format:
     * [
     *    'Header-Name' => 'Header Value'
     * ]
     * 
     * @param string $url
     * @param string[] $headers
     * @return string
     */
    public function doGetRequest($url, array $headers);
}
