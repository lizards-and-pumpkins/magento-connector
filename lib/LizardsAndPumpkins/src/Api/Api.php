<?php

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

    /**
     * @param string $url
     * @param HttpApiClient $client
     */
    public function __construct($url, HttpApiClient $client)
    {
        $this->url = rtrim($url, '/') . '/';
        $this->client = $client;
    }

    /**
     * @param string $filename
     * @param string $dataVersion
     */
    public function triggerCatalogImport($filename, $dataVersion)
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.catalog_import.v2+json'];

        $url = $this->url . self::API_ENDPOINT_CATALOG_IMPORT;
        $this->sendApiRequestWithFilename($filename, $dataVersion, $url, $headers);
    }

    /**
     * @param string $filename
     * @param string $dataVersion
     * @param string $url
     * @param string[] $headers
     */
    private function sendApiRequestWithFilename($filename, $dataVersion, $url, array $headers)
    {
        $this->validateFilename($filename);
        $body = json_encode(['fileName' => $filename, 'dataVersion' => $dataVersion]);
        $this->client->doPutRequest($url, $body, $headers);
    }

    /**
     * @param string $filename
     */
    private function validateFilename($filename)
    {
        $dir = dirname($filename);
        if ($dir !== '.') {
            throw new \UnexpectedValueException(sprintf('Filename "%s" should be a filename, no path.', $filename));
        }
    }

    /**
     * @param string $id
     * @param string $dataVersion
     * @param string $content
     * @param string[] $context
     * @param string[] $keyGeneratorParameters
     */
    public function triggerCmsBlockUpdate($id, $dataVersion, $content, array $context, array $keyGeneratorParameters)
    {
        if (!is_string($id)) {
            throw new InvalidUrlException(sprintf('The CMS Block ID/URL has to be a string, got %s', gettype($id)));
        }

        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.content_blocks.v2+json'];
        $url = $this->url . self::API_ENDPOINT_CONTENT_BLOCK_UPDATE . $id;
        $body = json_encode(array_merge(
            ['content' => $content, 'context' => $context, 'data_version' => $dataVersion],
            $keyGeneratorParameters
        ));

        $this->client->doPutRequest($url, $body, $headers);
    }

    /**
     * @return mixed[]
     */
    public function getCurrentVersion()
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.current_version.v1+json'];
        $url = $this->url . self::API_ENDPOINT_CURRENT_VERSION;
        $response = $this->client->doGetRequest($url, $headers);

        return json_decode($response, true);
    }

    /**
     * @param string $newVersion
     */
    public function setCurrentVersion($newVersion)
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.current_version.v1+json'];
        $url = $this->url . self::API_ENDPOINT_CURRENT_VERSION;
        $body = json_encode(['current_version' => $newVersion]);

        $this->client->doPutRequest($url, $body, $headers);
    }
}
