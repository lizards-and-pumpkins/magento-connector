<?php

namespace LizardsAndPumpkins\MagentoConnector\Api;

class InsecurePhpStreamHttpApiClient extends PhpStreamHttpApiClient
{
    /**
     * @param mixed[] $streamContextOptions
     * @return mixed[]
     */
    final protected function buildStreamContextOptions(array $streamContextOptions)
    {
        return array_merge(
            parent::buildStreamContextOptions($streamContextOptions),
            ['verify_peer' => false, 'verify_peer_name' => false]
        );
    }
}
