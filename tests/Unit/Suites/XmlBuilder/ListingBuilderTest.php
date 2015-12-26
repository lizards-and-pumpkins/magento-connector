<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class ListingBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $urlKey
     * @dataProvider provideInvalidUrlKey
     */
    public function testExceptionForInvalidUrlKey($urlKey)
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
    public function testExceptionForInvalidLocale($locale)
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

    public function testUrlKeyInListingNode()
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
     * @depends testUrlKeyInListingNode
     */
    public function testLocaleInListingNode()
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
     * @depends testUrlKeyInListingNode
     */
    public function testWebsiteInListingNode()
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

    public function testForExceptionOnInvalidAttribute()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $listingBuilder->addFilterCriterion(new \stdClass(), 'Equal', 'sale');
    }

    public function testExceptionForInvalidOperationOnFilter()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $locale = 'en_DK';
        $website = 'ru_de';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $listingBuilder->addFilterCriterion('category', new \stdClass(), 'sale');
    }

    public function testExceptionForInvalidAttributeOnFilter()
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
     * @dataProvider provideValidFilter
     */
    public function testOneFilterForListing($validAttribute, $validOperation, $validValue)
    {
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create('urlkey', $website, $locale);
        $listingBuilder->addFilterCriterion($validAttribute, $validOperation, $validValue);
        $xml = $listingBuilder->buildXml()->getXml();
        $this->assertContains("<$validAttribute is=\"$validOperation\">$validValue</$validAttribute>", $xml);
    }

    /**
     * @return string[]
     */
    public function provideValidFilter()
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

        $filter = [];
        foreach ($validOperations as $operation) {
            $filter[] = [
                'attribute' => 'category',
                'operation' => $operation,
                'value'     => 'sale',
            ];
        }
        return $filter;
    }

    public function testFilterWithLeadingSlash()
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

    public function testContainsCondition()
    {
        $xml = '<criteria type="and"><category is="Equal">accessoires</category></criteria>';

        $category = 'accessoires';
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create($category, $website, $locale);
        $listingBuilder->addFilterCriterion('category', 'Equal', $category);
        $this->assertContains($xml, $listingBuilder->buildXml()->getXml());
    }

    public function testContainsConditions()
    {
        $xml =
            '<criteria type="and"><category is="Equal">accessoires</category><stock_qty is="GreaterThan">0</stock_qty></criteria>';

        $category = 'accessoires';
        $website = 'ru_de';
        $locale = 'en_DK';
        $listingBuilder = ListingBuilder::create($category, $website, $locale);
        $listingBuilder->addFilterCriterion('category', 'Equal', $category);
        $listingBuilder->addFilterCriterion('stock_qty', 'GreaterThan', '0');
        $this->assertContains($xml, $listingBuilder->buildXml()->getXml());
    }
}
