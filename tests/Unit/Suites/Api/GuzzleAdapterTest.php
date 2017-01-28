<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class GuzzleAdapterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var array
     */
    private $requests;

    /**
     * @var callable
     */
    private $history;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var GuzzleAdapter
     */
    private $client;

    protected function setUp()
    {
        $this->mockHandler = new MockHandler();

        $stack = HandlerStack::create($this->mockHandler);

        $this->requests = [];
        $this->history = Middleware::history($this->requests);

        $stack->push($this->history);

        $this->guzzle = new Client(['handler' => $stack]);

        $this->client = new GuzzleAdapter($this->guzzle);
    }

    public function testGetRequest()
    {
        $url = 'http://example.com/123';
        $body = 'BODY';
        $headers = [
            'Content-Length' => '348',
            'Content-MD5'    => 'Q2hlY2sgSW50ZWdyaXR5IQ==',
            'Content-Type'   => 'application/x-www-form-urlencoded',
            'Date'           => 'Tue, 15 Nov 1994 08:12:31 GMT',
        ];
        $responseBody = 'RESPONSE-BODY';

        $this->mockHandler->append(new Response(200, [], $responseBody));

        $response = $this->client->getRequest($url, $body, $headers);

        $this->assertEquals($responseBody, $response);

        $this->assertCount(1, $this->requests);
        /** @var Request $request */
        $request = $this->requests[0]['request'];
        $this->assertEquals($url, (string)$request->getUri());
        $this->assertEquals($body, $request->getBody());
        $this->assertEquals('GET', $request->getMethod());

        foreach ($headers as $fieldName => $value) {
            $this->assertEquals($value, $request->getHeader($fieldName)[0]);
        }
    }

    public function testPutRequest()
    {
        $url = 'http://example.com/123';
        $body = 'BODY';
        $headers = [
            'Content-Length' => '348',
            'Content-MD5'    => 'Q2hlY2sgSW50ZWdyaXR5IQ==',
            'Content-Type'   => 'application/x-www-form-urlencoded',
            'Date'           => 'Tue, 15 Nov 1994 08:12:31 GMT',
        ];
        $responseBody = 'RESPONSE-BODY';

        $this->mockHandler->append(new Response(200, [], $responseBody));

        $response = $this->client->putRequest($url, $body, $headers);

        $this->assertEquals($responseBody, $response);

        $this->assertCount(1, $this->requests);
        /** @var Request $request */
        $request = $this->requests[0]['request'];
        $this->assertEquals($url, (string)$request->getUri());
        $this->assertEquals($body, $request->getBody());
        $this->assertEquals('PUT', $request->getMethod());

        foreach ($headers as $fieldName => $value) {
            $this->assertEquals($value, $request->getHeader($fieldName)[0]);
        }
    }

    public function testInvalidDomain()
    {
        $this->expectException(InvalidHostException::class);
        $this->mockHandler->append(new Response(200, [], '$responseBody'));
        $this->client->getRequest('', '', []);


    }

}
