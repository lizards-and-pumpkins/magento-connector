<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

class InsecurePhpStreamHttpApiClient extends PhpStreamHttpApiClient
{
    protected function buildStreamContextOptions(array $streamContextOptions)
    {
        return array_merge(
            parent::buildStreamContextOptions($streamContextOptions),
            ['verify_peer' => false, 'verify_peer_name' => false]
        );
    }
}
