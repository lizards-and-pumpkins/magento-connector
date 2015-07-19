<?php

namespace Brera\MagentoConnector\Product;

class XmlBuilderTest extends \PHPUnit_Framework_TestCase
{
    const XML_START = '<?xml version="1.0" encoding="utf-8"?>';

    public function testProductBuildsEmptyXml()
    {
        $xmlBuilder = new XmlBuilder([], []);
        $xml = $xmlBuilder->getXmlString();
        $this->assertEquals(self::XML_START, $xml);
    }

    public function testXmlWithProductNode()
    {
        $productDate = array();
    }
}
