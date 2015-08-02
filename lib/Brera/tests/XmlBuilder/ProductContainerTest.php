<?php
namespace Brera\MagentoConnector\XmlBuilder;

class ProductContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsSameDocument()
    {
        $xml = "<?xml version=\"1.0\"?>\n<xml/>";
        $container = new ProductContainer($xml);
        $this->assertSame('<xml/>', $container->getXml());
        $this->assertNotContains('<?xml', $container->getXml());
    }
}
