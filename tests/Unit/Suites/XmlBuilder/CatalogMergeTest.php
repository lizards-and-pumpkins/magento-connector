<?php
declare(strict_types=1);

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
        $this->merge->addProduct(new XmlString('<?xml version="1.0"?>' . $expectedXml));
        $xml = $this->merge->finish();
        $this->assertContains($expectedXml, $xml);
        $this->assertRegExp('#<products>#', $xml);
    }

    public function testPartialString()
    {
        $expectedXml = '<product>my product</product>';
        $this->merge->addProduct(new XmlString('<?xml version="1.0"?>' . $expectedXml));
        $xml = $this->merge->getPartialXmlString();
        $this->assertContains($expectedXml, $xml);
        $this->assertRegExp('#<products>#', $xml);
        $this->assertNotContains('</products>', $xml);

        $xml = $this->merge->finish();
        $this->assertContains('</products>', $xml);
        $this->assertContains('</catalog>', $xml);
    }
}
