<?php

namespace Brera\MagentoConnector\XmlBuilder;

/**
 * @covers \Brera\MagentoConnector\XmlBuilder\ProductBuilder
 */
class ProductBuilderTest extends \PHPUnit_Framework_TestCase
{
    const XML_START = '<?xml version="1.0" encoding="UTF-8"?>';

    public function testProductBuildsEmptyXml()
    {
        $xml = $this->getProductBuilderXml([]);
        $this->assertStringStartsWith(self::XML_START, $xml);
    }

    public function testXmlWithProductNode()
    {
        $xml = $this->getProductBuilderXml([]);

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
        $xml = $this->getProductBuilderXml($productData);

        // TODO exchange with XPath constraint
        $this->assertContains('type="simple"', $xml);
        $this->assertContains('sku="123"', $xml);
        $this->assertContains('<visibility>3</visibility>', $xml);
        $this->assertContains('tax_class="7"', $xml);
    }

    public function testXmlWithNodes()
    {
        $productData = [
            'url_key' => ''
        ];
        $xml = $this->getProductBuilderXml($productData);

        // TODO exchange with XPath constraint
        $this->assertContains('<url_key></url_key>', $xml);
    }

    public function testXmlWithEmptyNodeName()
    {
        $this->setExpectedException(\DOMException::class, 'Invalid Character Error');
        $productData = [
            'url_key'
        ];
        $xml = $this->getProductBuilderXml($productData);
    }

    public function testImageNode()
    {
        $productData = [
            'images' => [
                [
                    'main'  => true,
                    'file'  => 'some/file/somewhere.png',
                    'label' => 'This is the label',
                ]
            ]
        ];
        $xml = $this->getProductBuilderXml($productData);

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
                ]
            ]
        ];
        $xml = $this->getProductBuilderXml($productData);

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
                ]
            ]
        ];
        $xml = $this->getProductBuilderXml($productData);

        // TODO exchange with XPath constraint
        $this->assertContains('<label/>', $xml);
    }

    /**
     * @param string[] $productData
     * @param string   $exceptionMessage
     *
     * @dataProvider getInvalidImageData
     */
    public function testInvalidImageArgument($productData, $exceptionMessage)
    {
        $this->setExpectedException(InvalidImageDefinitionException::class, $exceptionMessage);
        $this->getProductBuilderXml($productData);
    }

    public function testEntityInNodeValue()
    {
        $productData = [
            'accessories_type' => 'Bags & Luggage'
        ];
        $xml = $this->getProductBuilderXml($productData);

        $this->assertContains('<accessories_type><![CDATA[Bags & Luggage]]></accessories_type>', $xml);
    }

    public function testCdataInNodeValue()
    {
        $productData = [
            'accessories_type' => '<![CDATA[Bags & Luggage]]>'
        ];
        $xml = $this->getProductBuilderXml($productData);

        $this->assertContains(
            '<accessories_type><![CDATA[<![CDATA[Bags & Luggage]]]]><![CDATA[]]></accessories_type>',
            $xml
        );
    }

    public function testCategoryForProduct()
    {
        $productData = [
            'categories' => ['shirts']
        ];
        $xml = $this->getProductBuilderXml($productData);

        $this->assertContains('<category>shirts</category>', $xml);
    }

    public function testMultipleCategories()
    {
        $productData = [
            'categories' => ['shirts', 'clothing']
        ];
        $xml = $this->getProductBuilderXml($productData);

        $this->assertContains('<category>shirts</category>', $xml);
        $this->assertContains('<category>clothing</category>', $xml);
    }

    public function testNoAssociatedProducts()
    {
        $productData = [
            'associated_products' => []
        ];
        $xml = $this->getProductBuilderXml($productData);

        $this->assertContains('<associated_products/>', $xml);
    }

    public function testUndefinedAssociatedProducts()
    {
        $productData = [];
        $xml = $this->getProductBuilderXml($productData);

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
                    ]
                ]
            ]
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
            ]
        ];
        $xml = $this->getProductBuilderXml($productData, ['locale' => 'de_DE']);
        $this->assertContains('<variations>', $xml);
        $this->assertContains('<attribute>color</attribute>', $xml);
        $this->assertContains('<attribute>size</attribute>', $xml);
    }

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
                        ]
                    ]
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
                        ]
                    ]
                ],
                '"file" must be a string.'
            ],
            [
                [
                    'images' => [
                        [
                            'main'  => true,
                            'file'  => 'some/file/somewhere.png',
                            'label' => 20,
                        ]
                    ]
                ],
                '"label" must be a string.'
            ],
            [
                [
                    'images' =>
                        [
                            'main'  => true,
                            'file'  => 'some/file/somewhere.png',
                            'label' => 20,
                        ]

                ],
                'images must be an array of image definitions.',
            ]
        ];
    }

    public function testProductContainerIsOne()
    {
        $xmlBuilder = new ProductBuilder([], []);
        $productContainer = $xmlBuilder->getProductContainer();
        $this->assertInstanceOf(ProductContainer::class, $productContainer);
    }

    /**
     * @param string[] $productData
     * @param string[] $context
     *
     * @return string
     */
    private function getProductBuilderXml($productData, $context = [])
    {
        $xmlBuilder = new ProductBuilder($productData, $context);
        $reflectionProperty = new \ReflectionProperty($xmlBuilder, 'xml');
        $reflectionProperty->setAccessible(true);

        /** @var $xml \XMLWriter */
        $xml = $reflectionProperty->getValue($xmlBuilder);

        return $xml->flush();
    }
}
