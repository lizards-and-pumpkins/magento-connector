<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge
 */
class CatalogMergeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CatalogMerge
     */
    private $merge;

    protected function setUp()
    {
        $this->merge = new CatalogMerge();
    }

    public function testEmptyXml()
    {
        $xml = $this->merge->finish();

        $namespaces = [
            'xmlns="http://lizardsandpumpkins\.com"',
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            'xsi:schemaLocation="http://lizardsandpumpkins\.com \.\./\.\./schema/catalog\.xsd"',
        ];
        foreach ($namespaces as $ns) {
            $this->assertRegExp("#<catalog .*$ns.*>#Us", $xml);
        }
    }

    public function testProductIsAdded()
    {
        $expectedXml = '<product>my product</product>';
        $xmlStart = $this->merge->addProduct(new XmlString('<?xml version="1.0"?>' . $expectedXml));
        $xml = $xmlStart . $this->merge->finish();
        $this->assertContains($expectedXml, $xml);
        $this->assertRegExp('#<products>#', $xml);
    }

    public function testPartialString()
    {
        $expectedXml = '<product>my product</product>';
        $xmlStart = $this->merge->addProduct(new XmlString('<?xml version="1.0"?>' . $expectedXml));
        $this->assertContains($expectedXml, $xmlStart);
        $this->assertRegExp('#<products>#', $xmlStart);
        $this->assertNotContains('</products>', $xmlStart);

        $xmlEnd = $this->merge->finish();
        $this->assertContains('</products>', $xmlEnd);
        $this->assertContains('</catalog>', $xmlEnd);
    }

    public function testAddsCategoryXmlString()
    {
        $expectedXml = '<listing>foo listing</listing>';
        $xmlStart = $this->merge->addCategory(new XmlString('<?xml version="1.0"?>' . $expectedXml));
        $xml = $xmlStart . $this->merge->finish();
        $this->assertContains($expectedXml, $xml);
        $this->assertRegExp('#<listings>#', $xml);
    }

    public function testThrowsExceptionsIfProductsAreAddedAfterCategories()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Products can only be added during product mode, this means BEFORE any category is added.'
        );

        $dummyCategoryXml = '<listing>foo</listing>';
        $dummyProductXml = '<product>foo</product>';
        $this->merge->addCategory(new XmlString('<?xml version="1.0"?>' . $dummyCategoryXml));
        $this->merge->addProduct(new XmlString($dummyProductXml));
        
    }
}
