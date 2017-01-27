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
            [''],
            ['some-string'],
        ];
    }

    public function testValidHost()
    {
        $host = 'https://api.lizardsAndPumpkins.io/api';
        $api = new Api($host);
        $this->assertInstanceOf(Api::class, $api);
    }

    public function testApiGetCurrentVersion()
    {

    }
}
