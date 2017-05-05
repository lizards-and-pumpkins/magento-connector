<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\XmlBuilder\XmlString
 */
class XmlStringTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsSameDocument()
    {
        $xml = "<?xml version=\"1.0\"?>\n<xml/>";
        $container = new XmlString($xml);
        $this->assertSame('<xml/>', $container->getXml());
        $this->assertNotContains('<?xml', $container->getXml());
    }
}
