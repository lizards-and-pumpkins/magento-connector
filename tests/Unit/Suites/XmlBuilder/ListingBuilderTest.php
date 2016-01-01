<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class ListingBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $string
     * @return string
     */
    private function removeXmlFormatting($string)
    {
        return str_replace('/\n|\t/m', '', $string);
    }

    /**
     * @param string $urlKey
     * @dataProvider provideInvalidUrlKey
     */
    public function testExceptionForInvalidUrlKeyIsThrown($urlKey)
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class);
        $website = 'ma';
        $locale = 'en_DK';
        ListingBuilder::create($urlKey, $website, $locale);
    }

    /**
     * @return array[]
     */
    public function provideInvalidUrlKey()
    {
        return [
            ['@'],
            ['hallo@lizardsandpumpkins.com'],
            ['german länguage'],
            ['category and products'],
            [''],
            [new \stdClass()],
            [12],
        ];
    }

    public function testUrlKeyHasNoLeadingSlash()
    {
        $urlKey = 'sneaker';
        $urlKeyWithLeadingSlash = '/' . $urlKey;

        $website = 'ma';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create($urlKeyWithLeadingSlash, $website, $locale);
        $xml = $listingBuilder->buildXml()->getXml();

        $this->assertContains($urlKeyWithLeadingSlash, $xml);
    }

    /**
     * @return array[]
     */
    public function provideValidUrlKey()
    {
        return [
            ['valid-url-key'],
            ['this"$()-lala-*!'],
            ['sneakershop/lala'],
        ];
    }

    /**
     * @param string $locale
     * @dataProvider provideInvalidLocale
     */
    public function testExceptionForInvalidLocaleIsThrown($locale)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $website = 'ma';
        ListingBuilder::create('valid-url-key', $website, $locale);
    }

    /**
     * @return array[]
     */
    public function provideInvalidLocale()
    {
        return [
            ['german'],
            ['ISO-8859-1'],
            ['us'],
            [''],
            [new \stdClass()],
            [['asd']],
        ];
    }

    public function testUrlKeyAttributeIsAddedToListingNodes()
    {
        $website = '42';
        $locale = 'cz_CN';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $xmlString = $listingBuilder->buildXml();
        $this->assertInstanceOf(XmlString::class, $xmlString);
        $xml = $xmlString->getXml();
        $this->assertRegExp(
            '#<listing .*url_key="urlkey".*?>#',
            $xml,
            'UrlKey as attribute is missing on listing node'
        );
    }

    /**
     * @depends testUrlKeyAttributeIsAddedToListingNodes
     */
    public function testLocaleAttributeIsAddedToListingNodes()
    {
        $website = 'fu';
        $locale = 'cs_CZ';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $xmlString = $listingBuilder->buildXml();
        $this->assertInstanceOf(XmlString::class, $xmlString);
        $xml = $xmlString->getXml();
        $this->assertRegExp(
            '#<listing .*locale="cs_CZ".*?>#',
            $xml,
            'Locale as attribute is missing on listing node'
        );
    }

    /**
     * @depends testUrlKeyAttributeIsAddedToListingNodes
     */
    public function testWebsiteAttributeIsAddedToListingNodes()
    {
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $xmlString = $listingBuilder->buildXml();
        $this->assertInstanceOf(XmlString::class, $xmlString);
        $xml = $xmlString->getXml();
        $this->assertRegExp(
            '#<listing .*website="ru_de".*?>#',
            $xml,
            'Website as attribute is missing on listing node'
        );
    }

    public function testExceptionOnInvalidAttributeIsThrown()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $listingBuilder->addFilterCriterion(new \stdClass(), 'Equal', 'sale');
    }

    public function testExceptionForInvalidOperationOnFilterIsThrown()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $locale = 'en_DK';
        $website = 'ru_de';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $listingBuilder->addFilterCriterion('category', new \stdClass(), 'sale');
    }

    public function testExceptionForInvalidAttributeOnFilterIsThrown()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $listingBuilder->addFilterCriterion(new \stdClass(), 'Equal', 'sale');
    }

    /**
     * @param string $validAttribute
     * @param string $validOperation
     * @param string $validValue
     * @dataProvider validFilterProvider
     */
    public function testOneFilterForListingIsCreated($validAttribute, $validOperation, $validValue)
    {
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $listingBuilder->addFilterCriterion($validAttribute, $validOperation, $validValue);
        $result = $listingBuilder->buildXml()->getXml();

        $expectedXml = sprintf('<%1$s is="%2$s">%3$s</%1$s>', $validAttribute, $validOperation, $validValue);

        $this->assertContains($expectedXml, $result);
    }

    /**
     * @return array[]
     */
    public function validFilterProvider()
    {
        $validOperations = [
            'Equal',
            'GreaterOrEqualThan',
            'GreaterThan',
            'LessOrEqualThan',
            'LessThan',
            'Like',
            'NotEqual',
        ];

        return array_map(function ($operation) {
            return [
                'attribute' => 'category',
                'operation' => $operation,
                'value' => 'sale',
            ];
        }, $validOperations);
    }

    public function testFilterWithLeadingSlashIsCreated()
    {
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $urlKey = 'sneaker';
        $urlKeyWithLeadingSlash = '/' . $urlKey;
        $listingBuilder->addFilterCriterion('category', 'Equal', $urlKeyWithLeadingSlash);
        $xml = $listingBuilder->buildXml()->getXml();
        $this->assertContains("<category is=\"Equal\">$urlKeyWithLeadingSlash</category>", $xml);
    }

    public function testXmlContainsOneCondition()
    {
        $category = 'accessoires';
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create($category, $website, $locale);
        $listingBuilder->addFilterCriterion('category', 'Equal', $category);

        $result = $listingBuilder->buildXml()->getXml();

        $this->assertContains('<category is="Equal">accessoires</category>', $result);
    }

    public function testXmlContainsMultipleConditions()
    {
        $category = 'accessoires';
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create($category, $website, $locale);
        $listingBuilder->addFilterCriterion('category', 'Equal', $category);
        $listingBuilder->addFilterCriterion('stock_qty', 'GreaterThan', '0');

        $result = $listingBuilder->buildXml()->getXml();

        $this->assertContains('<category is="Equal">accessoires</category>', $result);
        $this->assertContains('<stock_qty is="GreaterThan">0</stock_qty>', $result);
    }

    public function testProductListingXmlContainsStockAvailabilityCriteria()
    {
        $category = 'foo';
        $website = 'ru_de';
        $locale = 'en_DK';

        $listingBuilder = ListingBuilder::create($category, $website, $locale);

        $listingBuilder->addFilterCriterion('category', 'Equal', $category);
        $listingBuilder->addFilterCriterion('stock_qty', 'GreaterThan', '0');

        $result = $listingBuilder->buildXml()->getXml();

        $expectedXml = <<<EOX
<criteria type="or">
    <stock_qty is="GreaterThan">0</stock_qty>
    <backorders is="Equal">true</backorders>
</criteria>
EOX;

        $this->assertContains($this->removeXmlFormatting($expectedXml), $this->removeXmlFormatting($result));
    }
}
