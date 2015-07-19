<?php

namespace Brera\MagentoConnector\Product;

class XmlBuilderTest extends \PHPUnit_Framework_TestCase
{
    const XML_START = '<?xml version="1.0" encoding="utf-8"?>';

    public function testProductBuildsEmptyXml()
    {
        $xmlBuilder = new XmlBuilder([], []);
        $xml = $xmlBuilder->getXmlString();
        $this->assertStringStartsWith(self::XML_START, $xml);
    }

    public function testXmlWithProductNode()
    {
        $xmlBuilder = new XmlBuilder([], []);
        $xml = $xmlBuilder->getXmlString();
        
        // TODO implement XPath Constraint and use this here
        $this->assertRegExp('#<product( |/>).*#', $xml);
    }

    public function testXmlWithAttributes()
    {
        $productData = [
            'type' => 'simple',
            'sku' => '123',
            'visibility' => 3,
            'tax_class_id' => 7,
        ];
        $xmlBuilder = new XmlBuilder($productData, []);
        $xml = $xmlBuilder->getXmlString();

        // TODO exchange with XPath constraint
        $this->assertContains('type="simple"', $xml);
        $this->assertContains('sku="123"', $xml);
        $this->assertContains('visibility="3"', $xml);
        $this->assertContains('tax_class_id="7"', $xml);
    }

    public function testXmlWithNodes()
    {
        $productData = [
            'url_key' => ''
        ];
        $xmlBuilder = new XmlBuilder($productData, []);
        $xml = $xmlBuilder->getXmlString();

        // TODO exchange with XPath constraint
        $this->assertContains('<url_key></url_key>', $xml);
    }

    public function testXmlWithEmptyNodeName()
    {
        $this->setExpectedException(\DOMException::class, 'Invalid Character Error');
        $productData = [
            'url_key'
        ];
        $xmlBuilder = new XmlBuilder($productData, []);
        $xml = $xmlBuilder->getXmlString();

    }
}
