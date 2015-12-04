<?php
namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\XmlBuilder\StockBuilder
 */
class StockBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StockBuilder
     */
    private $stockBuilder;

    public function setUp()
    {
        $this->stockBuilder = new StockBuilder();
    }

    public function testStockBuilder()
    {
        $this->assertInstanceOf(StockBuilder::class, $this->stockBuilder);
    }

    public function testRootNode()
    {
        $xml = $this->stockBuilder->getXml();
        $this->assertContains('<update/>', $xml);
    }

    public function testAddStockData()
    {
        $this->stockBuilder->addStockData('foo', 200);
        $xml = $this->stockBuilder->getXml();
        $this->assertContains('<sku>foo</sku>', $xml);
        $this->assertContains('<quantity>200</quantity>', $xml);
    }
}
