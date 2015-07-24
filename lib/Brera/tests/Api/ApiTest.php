<?php

namespace Brera\MagentoConnector\Api;

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

}
