<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\Api\Api
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request[]
     */
    private $requests;

    /**
     * @var string
     */
    private $host = 'https://api.lizardsandpumpkins.io/api';

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var Middleware
     */
    private $history;

    /**
     * @var Api
     */
    private $api;

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
            [''],
            ['some-string'],
        ];
    }

    public function testValidHost()
    {
        $this->setUp();
        $this->assertInstanceOf(Api::class, $this->api);
    }

    /**
     * @see http://docs.guzzlephp.org/en/latest/testing.html
     */
    public function setUp()
    {
        $this->api = new Api($this->host);

        $this->mockHandler = new MockHandler();

        $stack = HandlerStack::create($this->mockHandler);

        $this->requests = [];
        $this->history = Middleware::history($this->requests);

        $stack->push($this->history);


        $client = new Client(['handler' => $stack]);
        $this->api->setClient($client);
    }

    public function testApiGetCurrentVersion()
    {
        $current = uniqid('current-', true);
        $previous = uniqid('previous-', true);
        $body = [
            'data' => [
                'current_version'  => $current,
                'previous_version' => $previous,
            ],
        ];

        $response = new Response(200, [], json_encode($body));
        $this->mockHandler->append($response);

        $response = $this->api->getCurrentVersion();

        $this->assertEquals($body, $response);

        $this->assertCount(1, $this->requests);
        /** @var Request $request */
        $request = $this->requests[0]['request'];
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals(
            'application/vnd.lizards-and-pumpkins.current_version.v1+json',
            $request->getHeader('Accept')[0]
        );
        $this->assertEquals($this->host . '/current_version/', (string)$request->getUri());
    }
}
