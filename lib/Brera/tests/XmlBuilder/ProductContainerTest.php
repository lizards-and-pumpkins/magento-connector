<?php
namespace Brera\MagentoConnector\Xml\Product;

class ProductContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsSameDocument()
    {
        /** @var $domDocument \PHPUnit_Framework_MockObject_MockBuilder|\DOMDocument */
        $domDocument = $this->getMock(\DOMDocument::class);
        $container = new ProductContainer($domDocument);
        $this->assertSame($domDocument, $container->getProductDomDocument());
    }
}
