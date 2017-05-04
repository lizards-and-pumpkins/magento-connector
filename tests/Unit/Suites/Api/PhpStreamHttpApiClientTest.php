<?php

namespace LizardsAndPumpkins\MagentoConnector\Api;

use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\Api\PhpStreamHttpApiClient
 */
class PhpStreamHttpApiClientTest extends TestCase
{
    public static $simulateFailureParsingUrl = false;
    public static $simulateHttpResponseBody = null;
    public static $streamContextOptionsSpy = null;

    /**
     * @param string $responseStatus
     * @return PhpStreamHttpApiClient
     */
    private function createTestApiClientWithResponseCode($responseStatus)
    {
        return new class($responseStatus) extends PhpStreamHttpApiClient {
            private $testResponseStatus;

            public function __construct($testResponseStatus)
            {
                $this->testResponseStatus = $testResponseStatus;
            }
            
            protected function getRawResponseHeaders(array $httpResponseHeaders = null)
            {
                return [$this->testResponseStatus];
            }
        };
    }

    protected function tearDown()
    {
        self::$simulateFailureParsingUrl = false;
        self::$simulateHttpResponseBody = null;
        self::$streamContextOptionsSpy = null;
    }
    
    public function testImplementsHttpApiClient()
    {
        $this->assertInstanceOf(HttpApiClient::class, new PhpStreamHttpApiClient());
    }

    public function testThrowsExceptionIfGetUrlIsEmpty()
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('Lizards & Pumpkins API URL must not be empty.');
        
        (new PhpStreamHttpApiClient())->doGetRequest('', []);
    }

    public function testThrowsExceptionIfPutUrlIsEmpty()
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('Lizards & Pumpkins API URL must not be empty.');
        
        (new PhpStreamHttpApiClient())->doPutRequest('', '', []);
    }

    public function testThrowsExceptionIfGetUrlCanNotBeParsed()
    {
        self::$simulateFailureParsingUrl = true;
        
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('Unable to parse Lizards & Pumpkins API URL "<foo this invalid url!>".');

        (new PhpStreamHttpApiClient())->doGetRequest('<foo this invalid url!>', []);
    }

    public function testThrowsExceptionIfGetUrlWithoutHostIsSpecified()
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('Unable to parse host from Lizards & Pumpkins API URL "/foo".');
        
        (new PhpStreamHttpApiClient())->doGetRequest('/foo', []);
    }

    public function testThrowsExceptionForNonHttpSchemaGetUrl()
    {
        $this->expectException(InvalidUrlException::class);
        $this->expectExceptionMessage('Lizards & Pumpkins API URL must start with "http" or "https", got "ssh://foo".');

        (new PhpStreamHttpApiClient())->doGetRequest('ssh://foo', []);
    }

    /**
     * @dataProvider failureHttpStatusProvider
     * @param string $failureHttpStatus
     */
    public function testThrowsAnExceptionOnNonSuccessfulGetRequests($failureHttpStatus)
    {
        $this->expectException(RequestFailedException::class);
        $expectedMessage = 'The HTTP response status code of the Lizards & Pumpkins API ' .
                   'is not within the expected 200-207 range, got ' . (int) $failureHttpStatus;
        $this->expectExceptionMessage($expectedMessage);
        self::$simulateHttpResponseBody = '';
        
        $failingTestApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 ' . $failureHttpStatus);
        $failingTestApiClient->doGetRequest('http://foo.com/rest/bar', []);
    }

    public function failureHttpStatusProvider()
    {
        return [
            'Lower boundary' => ['199 Unreal Status Code'],
            'Upper boundary' => ['208 Already Reported'],
            'Beyond upper boundary' => ['403 Forbidden']
        ];
    }

    /**
     * @dataProvider successHttpStatusProvider
     * @param string $successHttpStatus
     */
    public function testReturnsTheResponseBodyForGetRequest($successHttpStatus)
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 ' . $successHttpStatus);
        self::$simulateHttpResponseBody = 'baz';
        $this->assertSame('baz', $testApiClient->doGetRequest('http://foo.com/rest/bar', []));
    }

    public function successHttpStatusProvider()
    {
        return [
            'Lower boundary' => ['200 OK'],
            'Within bounds' => ['201 Accepted'],
            'Upper boundary' => ['207 Multi-Status'],
        ];
    }

    public function testUsesHttpGETMethodForGetRequest()
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 200 OK');
        self::$simulateHttpResponseBody = 'baz';
        $testApiClient->doGetRequest('http://bar.foo', []);

        $this->assertSame('GET', self::$streamContextOptionsSpy['http']['method']);
    }
    
    public function testSetsAnEmptyRequestBodyForGetRequests()
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 200 OK');
        self::$simulateHttpResponseBody = 'baz';
        $testApiClient->doGetRequest('http://bar.foo', []);
        
        $this->assertArrayNotHasKey('content', self::$streamContextOptionsSpy['http']);
    }

    public function testSetsTheSpecifiedHeadersOnGetRequests()
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 200 OK');
        self::$simulateHttpResponseBody = 'qux';
        $testApiClient->doGetRequest('http://bar.foo', ['Foo-Bar' => 'Baz']);

        $this->assertSame(['Foo-Bar: Baz'], self::$streamContextOptionsSpy['http']['header']);
    }

    public function testReturnsTheResponseBodyForPutRequest()
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 200 OK');
        self::$simulateHttpResponseBody = 'qux';
        $this->assertSame('qux', $testApiClient->doPutRequest('http://foo.com/rest/bar', '', []));
    }

    public function testUsesHttpPUTMethodForPutRequest()
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 200 OK');
        self::$simulateHttpResponseBody = 'baz';
        $testApiClient->doPutRequest('http://bar.foo', '', []);

        $this->assertSame('PUT', self::$streamContextOptionsSpy['http']['method']);
    }

    public function testSetsTheSpecifiedRequestBodyForPutRequests()
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 200 OK');
        self::$simulateHttpResponseBody = 'qux';
        $testApiClient->doPutRequest('http://bar.foo', 'baz', []);

        $this->assertSame('baz', self::$streamContextOptionsSpy['http']['content']);
    }

    public function testSetsTheSpecifiedHeadersOnPutRequests()
    {
        $testApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 200 OK');
        self::$simulateHttpResponseBody = 'qux';
        $testApiClient->doPutRequest('http://bar.foo', '', ['Foo-Bar' => 'Baz', 'Fizz' => 'Buz']);
        
        $this->assertSame([
            'Foo-Bar: Baz',
            'Fizz: Buz'
        ], self::$streamContextOptionsSpy['http']['header']);
    }
    
    public function testThrowsAnExceptionOnNonSuccessfulPutRequests()
    {
        $this->expectException(RequestFailedException::class);
        $expectedMessage = 'The HTTP response status code of the Lizards & Pumpkins API ' .
                          'is not within the expected 200-207 range, got 500';
        $this->expectExceptionMessage($expectedMessage);
        self::$simulateHttpResponseBody = '';

        $failingTestApiClient = $this->createTestApiClientWithResponseCode('HTTP/1.1 500 Server Error');
        $failingTestApiClient->doPutRequest('http://foo.bar/rest', '', []);
    }
}

function parse_url($url, $component = -1)
{
    if (PhpStreamHttpApiClientTest::$simulateFailureParsingUrl) {
        return false;
    }
    return \parse_url($url, $component);
}


function file_get_contents($filename, $flags = null, $context = null)
{
    if (null !== PhpStreamHttpApiClientTest::$simulateHttpResponseBody) {
        return PhpStreamHttpApiClientTest::$simulateHttpResponseBody;
    }
    return \file_get_contents($filename, $flags, $context);
}

function stream_context_create(array $options = null, array $params = null)
{
    PhpStreamHttpApiClientTest::$streamContextOptionsSpy = $options;
    return \stream_context_create($options, $params);
}
