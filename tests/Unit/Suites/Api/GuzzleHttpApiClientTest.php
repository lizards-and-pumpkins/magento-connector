<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class GuzzleHttpApiClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var GuzzleClient
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

        $this->guzzle = new GuzzleClient(['handler' => $stack]);

        $this->client = new GuzzleHttpApiClient($this->guzzle);
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

    /**
     * @dataProvider provideNon2xxStatusCodes
     * @param int $statusCode
     */
    public function testThrowsExceptionOnNon200GetResponse(int $statusCode)
    {
        $this->expectException(RequestFailedException::class);
        $url = 'http://example.com/123';
        $body = '';
        $headers = [];
        $responseBody = '';

        $this->mockHandler->append(new Response($statusCode, [], $responseBody));

        $this->client->getRequest($url, $body, $headers);
    }

    /**
     * @dataProvider provideNon2xxStatusCodes
     * @param int $statusCode
     */
    public function testThrowsExceptionOnNon202PutResponse(int $statusCode)
    {
        $this->expectException(RequestFailedException::class);
        $url = 'http://example.com/123';
        $body = '';
        $headers = [];
        $responseBody = '';

        $this->mockHandler->append(new Response($statusCode, [], $responseBody));

        $this->client->putRequest($url, $body, $headers);
    }

    public function provideNon2xxStatusCodes(): array
    {
        return $this->provideStatusCodes([200, 201, 202, 203, 204, 205, 206, 207, 208, 226]);
    }

    private function provideStatusCodes(array $except = []): array
    {
        $statusCodes = [
            'Continue'                           => [100],
            'Switching Protocols'                => [101],
            'Processing'                         => [102],
            'OK'                                 => [200],
            'Created'                            => [201],
            'Accepted'                           => [202],
            'Non-authoritative Information'      => [203],
            'No Content'                         => [204],
            'Reset Content'                      => [205],
            'Partial Content'                    => [206],
            'Multi-Status'                       => [207],
            'Already Reported'                   => [208],
            'IM Used'                            => [226],
            'Multiple Choices'                   => [300],
            'Moved Permanently'                  => [301],
            'Found'                              => [302],
            'See Other'                          => [303],
            'Not Modified'                       => [304],
            'Use Proxy'                          => [305],
            'Temporary Redirect'                 => [307],
            'Permanent Redirect'                 => [308],
            'Bad Request'                        => [400],
            'Unauthorized'                       => [401],
            'Payment Required'                   => [402],
            'Forbidden'                          => [403],
            'Not Found'                          => [404],
            'Method Not Allowed'                 => [405],
            'Not Acceptable'                     => [406],
            'Proxy Authentication Required'      => [407],
            'Request Timeout'                    => [408],
            'Conflict'                           => [409],
            'Gone'                               => [410],
            'Length Required'                    => [411],
            'Precondition Failed'                => [412],
            'Payload Too Large'                  => [413],
            'Request-URI Too Long'               => [414],
            'Unsupported Media Type'             => [415],
            'Requested Range Not Satisfiable'    => [416],
            'Expectation Failed'                 => [417],
            'I\'m a teapot'                      => [418],
            'Misdirected Request'                => [421],
            'Unprocessable Entity'               => [422],
            'Locked'                             => [423],
            'Failed Dependency'                  => [424],
            'Upgrade Required'                   => [426],
            'Precondition Required'              => [428],
            'Too Many Requests'                  => [429],
            'Request Header Fields Too Large'    => [431],
            'Connection Closed Without Response' => [444],
            'Unavailable For Legal Reasons'      => [451],
            'Client Closed Request'              => [499],
            'Internal Server Error'              => [500],
            'Not Implemented'                    => [501],
            'Bad Gateway'                        => [502],
            'Service Unavailable'                => [503],
            'Gateway Timeout'                    => [504],
            'HTTP Version Not Supported'         => [505],
            'Variant Also Negotiates'            => [506],
            'Insufficient Storage'               => [507],
            'Loop Detected'                      => [508],
            'Not Extended'                       => [510],
            'Network Authentication Required'    => [511],
            'Network Connect Timeout Error'      => [599],
        ];

        return array_filter($statusCodes, function ($arrayCode) use ($except) {
            return !in_array($arrayCode[0], $except, true);
        });
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

        $this->mockHandler->append(new Response(202, [], $responseBody));

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

    /**
     * @param string $url
     * @dataProvider provideInvalidUrls
     */
    public function testInvalidDomainOnGetRequest(string $url)
    {
        $this->expectException(InvalidHostException::class);
        $this->client->getRequest($url, '', []);
    }

    /**
     * @param string $url
     * @dataProvider provideInvalidUrls
     */
    public function testInvalidDomainOnPutRequest(string $url)
    {
        $this->expectException(InvalidHostException::class);
        $this->client->putRequest($url, '', []);
    }

    public function provideInvalidUrls()
    {
        return [
            'empty-url'       => [''],
            'non-http(s)-url' => ['ftp://example.com'],
            'no-host'         => ['/directory'],
        ];
    }
}
