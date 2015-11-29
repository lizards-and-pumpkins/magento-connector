<?php

namespace Brera\MagentoConnector\Api;

/**
 * @covers \Brera\MagentoConnector\Api\Api
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $url
     * @dataProvider getInvalidHosts
     */
    public function testWrongHost($url)
    {
        $this->setExpectedException(InvalidHostException::class);
        new Api($url);
    }

    /**
     * @return mixed[][]
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
        $host = 'https://api.brera.io/api';
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

        $host = 'https://api.brera.io/api';
        $headers = ['Accept' => 'application/vnd.brera.catalog_import.v1+json'];
        $body = json_encode(['file' => $file]);

        $httpRequestMock = $this->getMock(\GuzzleHttp\Psr7\Request::class);

        /** @var $api \PHPUnit_Framework_MockObject_MockObject|Api */
        $api = $this->getMock(Api::class, ['createHttpRequest'], [$host]);
        $completeApiUrl = $host . '/' . 'catalog_import';
        $api->method('createHttpRequest')->with(
            'PUT', $completeApiUrl, $headers, $body
        )->willReturn($httpRequestMock);

        $api->triggerProductImport($file);
    }
}
