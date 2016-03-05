<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\XmlBuilder\ProductBuilder
 */
class ProductBuilderTest extends \PHPUnit_Framework_TestCase
{
    const XML_START = '<?xml version="1.0" encoding="UTF-8"?>';

    /**
     * @param string[] $productData
     * @param string[] $context
     * @return string
     */
    private function getProductBuilderXml($productData, $context)
    {
        $xmlBuilder = new ProductBuilder($productData, $context);
        $reflectionProperty = new \ReflectionProperty($xmlBuilder, 'xml');
        $reflectionProperty->setAccessible(true);

        /** @var $xml \XMLWriter */
        $xml = $reflectionProperty->getValue($xmlBuilder);

        return $xml->flush();
    }

    private function getValidContext()
    {
        return ['locale' => 'cs_CZ'];
    }

    public function testProductBuildsEmptyXml()
    {
        $xml = $this->getProductBuilderXml([], $this->getValidContext());
        $this->assertStringStartsWith(self::XML_START, $xml);
    }

    public function testXmlWithAttributes()
    {
        $productData = [
            'type_id'    => 'simple',
            'sku'        => '123',
            'tax_class'  => 7,
            'attributes' => [
                'visibility' => 3,
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $productNode = simplexml_load_string($xml);
        $this->assertXmlAttribute($productData['type_id'], 'type', $productNode);
        $this->assertXmlAttribute($productData['sku'], 'sku', $productNode);
        $this->assertXmlAttribute($productData['tax_class'], 'tax_class', $productNode);

        $this->assertXmlAttribute('visibility', 'name', $productNode->attributes->attribute);
        $this->assertXmlAttribute('cs_CZ', 'locale', $productNode->attributes->attribute);
        $this->assertSame($productData['attributes']['visibility'], (int)$productNode->attributes->attribute);
    }

    public function testXmlWithNodes()
    {
        $productData = [
            'attributes' => [
                'url_key' => '',
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $productNode = simplexml_load_string($xml);
        $this->assertSame($productData['attributes']['url_key'], (string)$productNode->attributes->attribute);
        $this->assertXmlAttribute('url_key', 'name', $productNode->attributes->attribute);
        $this->assertXmlAttribute('cs_CZ', 'locale', $productNode->attributes->attribute);
    }

    public function testXmlWithEmptyNodeName()
    {
        $this->expectException(\DOMException::class);
        $this->expectExceptionMessage('Invalid Character Error');
        $productData = [
            'attributes' => [
                'url_key',
            ],
        ];
        $this->getProductBuilderXml($productData, $this->getValidContext());
    }

    public function testImageNode()
    {
        $productData = [
            'images' => [
                [
                    'main'  => true,
                    'file'  => 'some/file/somewhere.png',
                    'label' => 'This is the label',
                ],
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $images = (array) simplexml_load_string($xml)->images;
        $this->assertNotEmpty($images);
        $this->assertArrayHasKey('image', $images);
        $this->assertSame('true', (string)$images['image']->main);
        $this->assertSame($productData['images'][0]['file'], (string)$images['image']->file);
        $this->assertSame($productData['images'][0]['label'], (string)$images['image']->label);
    }

    public function testImageMainIsNull()
    {
        $productData = [
            'images' => [
                [
                    'file'  => 'some/file/somewhereElse.png',
                    'label' => 'Label',
                ],
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $images = (array) simplexml_load_string($xml)->images;
        $this->assertSame('false', (string)$images['image']->main);
    }

    public function testImageLabelIsNull()
    {
        $productData = [
            'images' => [
                [
                    'file'  => 'some/file/somewhereElse.png',
                    'label' => null,
                ],
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $images = (array) simplexml_load_string($xml)->images;
        
        $this->assertArrayHasKey('label', (array) $images['image']);
        $this->assertSame('', (string)$images['image']->label);
    }

    /**
     * @param string[] $productData
     * @param string $exceptionMessage
     * @dataProvider getInvalidImageData
     */
    public function testInvalidImageArgument($productData, $exceptionMessage)
    {
        $this->expectException(InvalidImageDefinitionException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->getProductBuilderXml($productData, $this->getValidContext());
    }

    /**
     * @return array[]
     */
    public function getInvalidImageData()
    {
        return [
            'invalid main type'  => [
                [
                    'images' => [
                        [
                            'main'  => 2,
                            'file'  => 'some/file/somewhere.png',
                            'label' => 'This is the label',
                        ],
                    ],
                ],
                '"main" must be either "true" or "false".',
            ],
            'invalid file type'  => [
                [
                    'images' => [
                        [
                            'main'  => true,
                            'file'  => 8,
                            'label' => 'This is the label',
                        ],
                    ],
                ],
                '"file" must be a string.',
            ],
            'invalid label type' => [
                [
                    'images' => [
                        [
                            'main'  => true,
                            'file'  => 'some/file/somewhere.png',
                            'label' => 20,
                        ],
                    ],
                ],
                '"label" must be a string.',
            ],
        ];
    }

    public function testEntityInNodeValue()
    {
        $productData = [
            'attributes' => ['accessories_type' => 'Bags & Luggage',],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains('<attribute name="accessories_type" locale="cs_CZ"><![CDATA[Bags & Luggage]]></attribute>',
            $xml);
    }

    public function testCdataInNodeValue()
    {
        $productData = [
            'attributes' => ['accessories_type' => '<![CDATA[Bags & Luggage]]>'],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains(
            '<attribute name="accessories_type" locale="cs_CZ"><![CDATA[<![CDATA[Bags & Luggage]]]]><![CDATA[]]></attribute>',
            $xml
        );
    }

    public function testCategoryForProduct()
    {
        $productData = [
            'attributes' => ['categories' => ['shirts']],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains('<attribute name="category" locale="cs_CZ">shirts</attribute>', $xml);
    }

    public function testMultipleCategories()
    {
        $productData = [
            'attributes' => ['categories' => ['shirts', 'clothing']],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains('<attribute name="category" locale="cs_CZ">shirts</attribute>', $xml);
        $this->assertContains('<attribute name="category" locale="cs_CZ">clothing</attribute>', $xml);
    }

    public function testNoAssociatedProducts()
    {
        $productData = [
            'associated_products' => [],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertNotContains('<associated_products/>', $xml);
    }

    public function testUndefinedAssociatedProducts()
    {
        $productData = [];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertNotContains('<associated_products', $xml);
    }

    public function testAssociatedProducts()
    {
        $productData = [
            'associated_products' => [
                [
                    'sku'        => 'associated-product-1',
                    'tax_class'  => 4,
                    'attributes' => [
                        'stock_qty' => 12,
                        'visible'   => true,
                        'color'     => 'green',
                    ],
                ],
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, ['locale' => 'de_DE']);

        $this->assertContains('<product sku="associated-product-1" tax_class="4"', $xml);
        $this->assertContains('<attributes>', $xml);
        $this->assertContains('<attribute name="stock_qty" locale="de_DE">12</attribute>', $xml);
        $this->assertContains('<attribute name="color" locale="', $xml);
        $this->assertNotContains('<label locale="', $xml);
        $this->assertContains('green</attribute>', $xml);
    }

    public function testVariations()
    {
        $productData = [
            'variations' => [
                'size',
                'color',
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());
        $this->assertContains('<variations>', $xml);
        $this->assertContains('<attribute>color</attribute>', $xml);
        $this->assertContains('<attribute>size</attribute>', $xml);
    }

    public function testXmlStringIsOne()
    {
        $xmlBuilder = new ProductBuilder([], $this->getValidContext());
        $xmlString = $xmlBuilder->getXmlString();
        $this->assertInstanceOf(XmlString::class, $xmlString);
    }

    /**
     * @param array[] $invalidContext
     * @return array
     * @dataProvider provideInvalidContext
     */
    public function testExceptionOnInvalidContext($invalidContext)
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductBuilder([], $invalidContext);
    }

    /**
     * @return array[]
     */
    public function provideInvalidContext()
    {
        return [
            'locale_empty'   => [[]],
            'invalid_format' => [['locale' => 'de_de']],
            'invalid_type'   => [['locale' => new \stdClass()]],
        ];
    }

    /**
     * @return array[]
     * @dataProvider provideInvalidAssociatedProducts
     */
    public function testExceptionOnInvalidAssociatedProducts($invalidAssociatedProducts)
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductBuilder($invalidAssociatedProducts, $this->getValidContext());
    }

    /**
     * @return array[]
     */
    public function provideInvalidAssociatedProducts()
    {
        return [
            'missing_stock_qty'   => [
                [
                    'associated_products' => [
                        [
                            'sku'          => 'associated-product-1',
                            'visible'      => true,
                            'tax_class_id' => 4,
                            'attributes'   => [
                                'color' => 'green',
                            ],
                        ],
                    ],
                ],
            ],
            'sku_missing'         => [
                [
                    'associated_products' => [
                        [
                            'stock_qty'    => 7,
                            'visible'      => true,
                            'tax_class_id' => 4,
                            'attributes'   => [
                                'color' => 'green',
                            ],
                        ],
                    ],
                ],
            ],
            'attributes_no_array' => [
                [
                    'associated_products' => [
                        [
                            'stock_qty'    => 7,
                            'sku'          => 'associated-product-1',
                            'visible'      => true,
                            'tax_class_id' => 4,
                            'attributes'   => 'no_array',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $expectedValue
     * @param string $attributeName
     * @param \SimpleXMLElement $simpleXMLElement
     */
    private function assertXmlAttribute($expectedValue, $attributeName, \SimpleXMLElement $simpleXMLElement)
    {
        $this->assertEquals($expectedValue, (string)$simpleXMLElement->attributes()[$attributeName]);
    }
}
