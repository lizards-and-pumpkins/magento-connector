<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

use function GuzzleHttp\json_encode;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\Api\Api
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $host = 'https://example.com/api';

    /**
     * @var HttpApiClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpClient;

    /**
     * @var Api
     */
    private $api;

    public function setUp()
    {
        $this->httpClient = $this->createMock(HttpApiClient::class);
        $this->api = new Api($this->host, $this->httpClient);
    }

    public function testApiGetCurrentVersion()
    {
        $current = uniqid('current-', true);
        $previous = uniqid('previous-', true);
        $responseBody = json_encode([
            'data' => [
                'current_version'  => $current,
                'previous_version' => $previous,
            ],
        ]);

        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.current_version.v1+json'];
        $url = $this->host . '/current_version/';
        $body = '';

        $this->httpClient->expects($this->once())
            ->method('getRequest')
            ->with(
                $this->equalTo($url),
                $this->equalTo($body),
                $this->equalTo($headers)
            )->willReturn($responseBody);

        $response = $this->api->getCurrentVersion();

        $this->assertEquals(json_decode($responseBody, true), $response);
    }

    public function testTriggerCmsBlockUpdate()
    {
        $responseBody = '';

        $context = ['locale' => 'de_DE', 'website' => 'WEBSITE'];
        $content = 'content';
        $keyGeneratorParameters = ['url_key' => 'super-url'];

        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.content_blocks.v1+json'];
        $url = $this->host . '/content_blocks/123';
        $body = json_encode(
            array_merge(
                [
                    'content' => $content,
                    'context' => $context,
                ],
                $keyGeneratorParameters
            )
        );

        $this->httpClient->expects($this->once())
            ->method('putRequest')
            ->with(
                $this->equalTo($url),
                $this->equalTo($body),
                $this->equalTo($headers)
            )->willReturn($responseBody);

        $this->api->triggerCmsBlockUpdate('123', $content, $context, $keyGeneratorParameters);
    }

    public function testTriggerProductImport()
    {
        $responseBody = '';

        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.catalog_import.v1+json'];
        $url = $this->host . '/catalog_import/';
        $body = json_encode(
            [
                'fileName' => 'catalog.xml',
            ]
        );

        $this->httpClient->expects($this->once())
            ->method('putRequest')
            ->with(
                $this->equalTo($url),
                $this->equalTo($body),
                $this->equalTo($headers)
            )->willReturn($responseBody);


        $this->api->triggerProductImport('catalog.xml');


    }
}
