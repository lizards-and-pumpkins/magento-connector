<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

interface HttpApiClient
{
    public function putRequest(string $url, string $body, array $headers): string;

    public function getRequest(string $url, string $body, array $headers): string;

}
