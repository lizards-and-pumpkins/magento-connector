<?php

namespace Brera\MagentoConnector\Api;

use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;

class Api
{
    /**
     * @var string
     */
    private $url;

    /**
     * @param string $url
     */
    public function __construct($url)
    {
        $this->checkHost($url);

        $this->url = $url;
    }

    private function checkHost($url)
    {
        if (!is_string($url)) {
            throw new InvalidHostException('Host must be of type string.');
        }

        $urlParts = parse_url($url);
        if ($urlParts === false) {
            throw new InvalidHostException('URL seems to be  seriously malformed.');
        }

        if (empty($urlParts['scheme']) || $urlParts['scheme'] !== 'https') {
            throw new InvalidHostException('Host should be called via HTTPS!');
        }

        if (empty($urlParts['host'])) {
            throw new InvalidDefinitionException('Domain must be specified.');
        }
    }


}
