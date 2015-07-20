<?php

namespace Brera\MagentoConnector\Xml\Product;

class ProductMergeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ProductMerge
     */
    private $merge;

    public function testEmptyXml()
    {
        $xml = $this->merge->getXmlString();

        $namespaces = [
            'xmlns="http://brera\.io"',
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            'xsi:schemaLocation="http://brera.io ../../schema/catalog.xsd"',
        ];
        foreach ($namespaces as $ns) {
            $this->assertRegExp("#<catalog .*$ns.*>#Us", $xml);
        }
        $this->assertRegExp('#<products( |/>).*#', $xml);
    }

    public function testProductIsAdded()
    {
        $product = new \DOMDocument('1.0', 'utf-8');
        $node = $product->createElement('product', 'my product');
        $product->appendChild($node);

        $this->merge->addProduct(new ProductContainer($product));
        $xml = $this->merge->getXmlString();
        $this->assertContains('<product>my product</product>', $xml);
    }

    protected function setUp()
    {
        $this->merge = new ProductMerge();
    }
}
