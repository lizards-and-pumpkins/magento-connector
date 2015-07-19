<?php

namespace Brera\MagentoConnector\Product;

class XmlBuilderTest extends \PHPUnit_Framework_TestCase
{
    const XML_START = '<?xml version="1.0" encoding="utf-8"?>';

    /**
     * @var XmlBuilder
     */
    private $xmlBuilder;

    protected function setUp()
    {
        $this->xmlBuilder = new XmlBuilder([], []);
    }


    public function testProductBuildsEmptyXml()
    {
        $xml = $this->xmlBuilder->getXmlString();
        $this->assertEquals(self::XML_START, $xml);
    }
}
