<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

class Api
{
    const API_ENDPOINT_CATALOG_IMPORT = 'catalog_import/';
    const API_ENDPOINT_CONTENT_BLOCK_UPDATE = 'content_blocks/';
    const API_ENDPOINT_CURRENT_VERSION = 'current_version';

    /**
     * @var string
     */
    private $url;

    /**
     * @var HttpApiClient
     */
    private $client;

    public function __construct(string $url, HttpApiClient $client)
    {
        $this->url = rtrim($url, '/') . '/';
        $this->client = $client;
    }

    public function triggerProductImport(string $filename)
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.catalog_import.v1+json'];

        $url = $this->url . self::API_ENDPOINT_CATALOG_IMPORT;
        $this->sendApiRequestWithFilename($filename, $url, $headers);
    }

    /**
     * @param string   $filename
     * @param string   $url
     * @param string[] $headers
     */
    private function sendApiRequestWithFilename(string $filename, string $url, array $headers)
    {
        $this->validateFilename($filename);
        $body = json_encode(['fileName' => $filename]);
        $this->client->putRequest($url, $body, $headers);
    }

    /**
     * @param string $filename
     */
    private function validateFilename(string $filename)
    {
        $dir = dirname($filename);
        if ($dir !== '.') {
            throw new \UnexpectedValueException(sprintf('Filename "%s" should be a filename, no path.', $filename));
        }
    }

    public function triggerProductStockImport(string $filename)
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.multiple_product_stock_quantity.v1+json'];

        $url = $this->url . self::API_ENDPOINT_STOCK_UPDATE;
        $this->sendApiRequestWithFilename($filename, $url, $headers);
    }

    /**
     * @param string   $id
     * @param string   $content
     * @param string[] $context
     * @param string[] $keyGeneratorParameters
     */
    public function triggerCmsBlockUpdate(string $id, string $content, array $context, array $keyGeneratorParameters)
    {
        if (!is_string($id)) {
            throw new InvalidUrlException();
        }

        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.content_blocks.v1+json'];
        $url = $this->url . self::API_ENDPOINT_CONTENT_BLOCK_UPDATE . $id;
        $body = json_encode(array_merge(['content' => $content, 'context' => $context], $keyGeneratorParameters));

        $this->client->putRequest($url, $body, $headers);
    }

    public function getCurrentVersion(): array
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.current_version.v1+json'];
        $url = $this->url . self::API_ENDPOINT_CURRENT_VERSION;
        $response = $this->client->getRequest($url, '', $headers);

        return json_decode($response, true);
    }

    public function setCurrentVersion(string $newVersion)
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.current_version.v1+json'];
        $url = $this->url . self::API_ENDPOINT_CURRENT_VERSION;
        $body = json_encode(['current_version' => $newVersion]);

        $this->client->putRequest($url, $body, $headers);
    }
}
