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

    public function testXmlWithProductNode()
    {
        $xml = $this->getProductBuilderXml([], $this->getValidContext());

        // TODO implement XPath Constraint and use this here
        $this->assertContains('<product><attributes/></product>', $xml);
    }

    public function testXmlWithAttributes()
    {
        $productData = [
            'type_id'      => 'simple',
            'sku'          => '123',
            'visibility'   => 3,
            'tax_class_id' => 7,
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        // TODO exchange with XPath constraint
        $this->assertContains('type="simple"', $xml);
        $this->assertContains('sku="123"', $xml);
        $this->assertContains('<visibility locale="cs_CZ">3</visibility>', $xml);
        $this->assertContains('tax_class="7"', $xml);
    }

    public function testXmlWithNodes()
    {
        $productData = [
            'url_key' => '',
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        // TODO exchange with XPath constraint
        $this->assertContains('<url_key locale="cs_CZ"></url_key>', $xml);
    }

    public function testXmlWithEmptyNodeName()
    {
        $this->setExpectedException(\DOMException::class, 'Invalid Character Error');
        $productData = [
            'url_key',
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());
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

        // TODO exchange with XPath constraint
        $this->assertContains('<images>', $xml);
        $this->assertContains('<image>', $xml);
        $this->assertContains('<main>true</main>', $xml);
        $this->assertContains('<file>some/file/somewhere.png</file>', $xml);
        $this->assertContains('<label>This is the label</label>', $xml);
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

        // TODO exchange with XPath constraint
        $this->assertContains('<main>false</main>', $xml);
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

        // TODO exchange with XPath constraint
        $this->assertContains('<label/>', $xml);
    }

    /**
     * @param string[] $productData
     * @param string   $exceptionMessage
     * @dataProvider getInvalidImageData
     */
    public function testInvalidImageArgument($productData, $exceptionMessage)
    {
        $this->setExpectedException(InvalidImageDefinitionException::class, $exceptionMessage);
        $this->getProductBuilderXml($productData, $this->getValidContext());
    }

    public function testEntityInNodeValue()
    {
        $productData = [
            'accessories_type' => 'Bags & Luggage',
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains('<accessories_type locale="cs_CZ"><![CDATA[Bags & Luggage]]></accessories_type>', $xml);
    }

    public function testCdataInNodeValue()
    {
        $productData = [
            'accessories_type' => '<![CDATA[Bags & Luggage]]>',
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains(
            '<accessories_type locale="cs_CZ"><![CDATA[<![CDATA[Bags & Luggage]]]]><![CDATA[]]></accessories_type>',
            $xml
        );
    }

    public function testCategoryForProduct()
    {
        $productData = [
            'categories' => ['shirts'],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains('<category locale="cs_CZ">shirts</category>', $xml);
    }

    public function testMultipleCategories()
    {
        $productData = [
            'categories' => ['shirts', 'clothing'],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains('<category locale="cs_CZ">shirts</category>', $xml);
        $this->assertContains('<category locale="cs_CZ">clothing</category>', $xml);
    }

    public function testNoAssociatedProducts()
    {
        $productData = [
            'associated_products' => [],
        ];
        $xml = $this->getProductBuilderXml($productData, $this->getValidContext());

        $this->assertContains('<associated_products/>', $xml);
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
                    'stock_qty'    => 12,
                    'sku'          => 'associated-product-1',
                    'visible'      => true,
                    'tax_class_id' => 4,
                    'attributes'   => [
                        'color' => 'green',
                    ],
                ],
            ],
        ];
        $xml = $this->getProductBuilderXml($productData, ['locale' => 'de_DE']);

        $this->assertContains('<product sku="associated-product-1" tax_class="4"', $xml);
        $this->assertContains('<attributes>', $xml);
        $this->assertContains('<stock_qty>12</stock_qty>', $xml);
        $this->assertContains('<color locale="', $xml);
        $this->assertNotContains('<label locale="', $xml);
        $this->assertContains('green</color>', $xml);
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

    /**
     * @return array[]
     */
    public function getInvalidImageData()
    {
        return [
            [
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
            [
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
            [
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
            [
                [
                    'images' =>
                        [
                            'main'  => true,
                            'file'  => 'some/file/somewhere.png',
                            'label' => 20,
                        ],

                ],
                'images must be an array of image definitions.',
            ],
        ];
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
    public function testInvalidContext($invalidContext)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
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
}
