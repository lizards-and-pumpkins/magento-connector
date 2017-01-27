<?php

namespace LizardsAndPumpkins\MagentoConnector\Api;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\Api\Api
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $url
     * @dataProvider getInvalidHosts
     */
    public function testWrongHost($url)
    {
        $this->expectException(InvalidHostException::class);
        new Api($url);
    }

    /**
     * @return array[]
     */
    public function getInvalidHosts()
    {
        return [
            [new \stdClass()],
            [''],
            ['some-string'],
            [0],
            [1.1],
            [null],
        ];
    }

    public function testValidHost()
    {
        $host = 'https://api.lizardsAndPumpkins.io/api';
        $api = new Api($host);
        $this->assertInstanceOf(Api::class, $api);
    }

    public function testApiIsTriggered()
    {
        $this->markTestSkipped(
            'No clue how to test this - but I\' pretty sure it is an implementation and not test problem'
        );

        return;

        $file = 'catalog.xml';

        $host = 'https://api.lizardsAndPumpkins.io/api';
        $headers = ['Accept' => 'application/vnd.lizardsAndPumpkins.catalog_import.v1+json'];
        $body = json_encode(['file' => $file]);

        $httpRequestMock = $this->createMock(\GuzzleHttp\Psr7\Request::class);

        /** @var $api \PHPUnit_Framework_MockObject_MockObject|Api */
        $api = $this->createMock(Api::class, ['createHttpRequest'], [$host]);
        $completeApiUrl = $host . '/' . 'catalog_import';
        $api->method('createHttpRequest')->with(
            'PUT', $completeApiUrl, $headers, $body
        )->willReturn($httpRequestMock);

        $api->triggerProductImport($file);
    }
}
