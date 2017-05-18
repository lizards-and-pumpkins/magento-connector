<?php

namespace LizardsAndPumpkins\MagentoConnector\Api;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/testOverloadedFunctionsInApiNamespace.php';

class InsecurePhpStreamHttpApiClientTest extends TestCase
{
    protected function tearDown()
    {
        ApiTestOverloadedPhpFunctions::clear();
    }
    
    public function testExtendsPhpStreamHttpApiClient()
    {
        $this->assertInstanceOf(PhpStreamHttpApiClient::class, new InsecurePhpStreamHttpApiClient());
    }
    
    public function testDisablesTLSPeerVerificationOnStreamContextOptions()
    {
        $client = new TestInsecurePhpStreamHttpApiClient();
        $client->doGetRequest('https://example.com', []);
        
        $this->assertSame(false, ApiTestOverloadedPhpFunctions::$streamContextOptionsSpy['http']['verify_peer']);
        $this->assertSame(false, ApiTestOverloadedPhpFunctions::$streamContextOptionsSpy['http']['verify_peer_name']);
    }
}

class TestInsecurePhpStreamHttpApiClient extends InsecurePhpStreamHttpApiClient
{
    protected function getRawResponseHeaders(array $httpResponseHeaders = null)
    {
        return ['HTTP/1.1 200 OK'];
    }
}
