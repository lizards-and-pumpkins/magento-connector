<?php
namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class ListingBuilderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param string $urlKey
     * @dataProvider provideInvalidUrlKey
     */
    public function testInvalidUrlKey($urlKey)
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class);
        ListingBuilder::create($urlKey, 'and');
    }

    /**
     * @return string[][]
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

    public function testUrlKeyHasLeadingSlash()
    {
        $urlKey = 'sneaker';
        $urlKeyWithLeadingSlash = '/' . $urlKey;

        $listingBuilder = ListingBuilder::create($urlKeyWithLeadingSlash, 'and');
        $xml = $listingBuilder->buildXml()->getXml();

        $this->assertNotContains($urlKeyWithLeadingSlash, $xml);
        $this->assertContains($urlKey, $xml);
    }

    /**
     * @param string $validUrlKey
     * @dataProvider provideValidUrlKey
     */
    public function testValidUrlKey($validUrlKey)
    {
        $builder = ListingBuilder::create($validUrlKey, 'and');
        $this->assertInstanceOf(ListingBuilder::class, $builder);
    }

    /**
     * @return string[][]
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
     * @depends testValidUrlKey
     */
    public function testValidCondition()
    {
        $builderOr = ListingBuilder::create('valid-url-key', 'or');
        $this->assertInstanceOf(ListingBuilder::class, $builderOr);
        $builderAnd = ListingBuilder::create('valid-url-key', 'and');
        $this->assertInstanceOf(ListingBuilder::class, $builderAnd);
    }

    /**
     * @depends testValidUrlKey
     */
    public function testInvalidCondition()
    {
        $condition = 'asdfasdfg';
        $this->setExpectedException(
            \InvalidArgumentException::class,
            sprintf('Condition must be either "and" or "or"', $condition)
        );

        ListingBuilder::create('valid-url-key', $condition);
    }

    /**
     * @param string $locale
     * @dataProvider provideInvalidLocale
     */
    public function testInvalidLocale($locale)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $builder = $this->createBuilder();
        $builder->setLocale($locale);
    }

    /**
     * @return string[][]
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

    /**
     * @param string $validLocale
     * @dataProvider provideValidLocale
     * @depends      testValidUrlKey
     * @depends      testValidCondition
     */

    public function testValidLocale($validLocale)
    {
        $builder = $this->createBuilder();
        $builder->setLocale($validLocale);
        $this->assertInstanceOf(ListingBuilder::class, $builder);
    }

    public function provideValidLocale()
    {
        return [
            ['de_DE'],
            ['cs_CZ'],
            ['en_US'],
            ['de_CH'],
            ['en_GB'],
        ];
    }

    /**
     * @depends testValidUrlKey
     * @depends testValidCondition
     */
    public function testUrlInListingXml()
    {
        $listingBuilder = $this->createBuilder('urlkey');
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
     * @depends testUrlInListingXml
     */
    public function testConditionInXml()
    {
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $xmlString = $listingBuilder->buildXml();
        $this->assertInstanceOf(XmlString::class, $xmlString);
        $xml = $xmlString->getXml();
        $this->assertRegExp(
            '#<listing .*condition="and".*?>#',
            $xml,
            'Condition as attribute is missing on listing node'
        );
    }

    /**
     * @depends testValidLocale
     * @depends testUrlInListingXml
     * @depends testConditionInXml
     */
    public function testLocaleInXml()
    {
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $listingBuilder->setLocale('cs_CZ');
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
     * @depends testUrlInListingXml
     * @depends testConditionInXml
     */
    public function testWebsiteInXml()
    {
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $listingBuilder->setWebsite('ru_de');
        $xmlString = $listingBuilder->buildXml();
        $this->assertInstanceOf(XmlString::class, $xmlString);
        $xml = $xmlString->getXml();
        $this->assertRegExp(
            '#<listing .*website="ru_de".*?>#',
            $xml,
            'Website as attribute is missing on listing node'
        );
    }

    /**
     * @depends testUrlInListingXml
     * @depends testConditionInXml
     * @depends testLocaleInXml
     * @depends testWebsiteInXml
     */
    public function testXmlComplete()
    {
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $listingBuilder->setWebsite('ru_de');
        $listingBuilder->setLocale('cs_CZ');
        $xmlString = $listingBuilder->buildXml();
        $this->assertInstanceOf(XmlString::class, $xmlString);
        $xml = $xmlString->getXml();
        $this->assertRegExp(
            '#<listing .*website="ru_de".*?>#',
            $xml,
            'Website as attribute is missing on listing node'
        );
        $this->assertRegExp(
            '#<listing .*locale="cs_CZ".*?>#',
            $xml,
            'Locale as attribute is missing on listing node'
        );
        $this->assertRegExp(
            '#<listing .*condition="and".*?>#',
            $xml,
            'Condition as attribute is missing on listing node'
        );
        $this->assertRegExp(
            '#<listing .*url_key="urlkey".*?>#',
            $xml,
            'UrlKey as attribute is missing on listing node'
        );
    }

    /**
     * @param string $maybeInvalidAttribute
     * @param string $maybeInvalidOperation
     * @param string $maybeInvalidValue
     * @dataProvider provideInvalidFilter
     */
    public function testInvalidFilter($maybeInvalidAttribute, $maybeInvalidOperation, $maybeInvalidValue)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $listingBuilder->addFilterCriterion($maybeInvalidAttribute, $maybeInvalidOperation, $maybeInvalidValue);
    }

    public function provideInvalidFilter()
    {
        return [
            [
                'attribute' => 'category',
                'operation' => new \stdClass(),
                'value'     => 'sale',
            ],
            [
                'attribute' => new \stdClass(),
                'operation' => 'Equals',
                'value'     => 'sale',
            ],
            [
                'attribute' => 'category',
                'operation' => 'Equals',
                'value'     => new \stdClass(),
            ],
            [
                'attribute' => 'category',
                'operation' => 'InvalidOperation',
                'value'     => new \stdClass(),
            ],
        ];
    }

    /**
     * @param string $validAttribute
     * @param string $validOperation
     * @param string $validValue
     * @dataProvider provideValidFilter
     */
    public function testOneFilterForListing($validAttribute, $validOperation, $validValue)
    {
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $listingBuilder->addFilterCriterion($validAttribute, $validOperation, $validValue);
        $xml = $listingBuilder->buildXml()->getXml();
        $this->assertContains("<$validAttribute operation=\"$validOperation\">$validValue</$validAttribute>", $xml);
    }

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
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $urlKey = 'sneaker';
        $urlKeyWithLeadingSlash = '/' . $urlKey;
        $listingBuilder->addFilterCriterion('category', 'Equal', $urlKeyWithLeadingSlash);
        $xml = $listingBuilder->buildXml()->getXml();
        $this->assertNotContains($urlKeyWithLeadingSlash, $xml);
        $this->assertContains("<category operation=\"Equal\">$urlKey</category>", $xml);
    }

    private function createBuilder($urlKey = 'valid-url-key', $condition = 'and')
    {
        return ListingBuilder::create($urlKey, $condition);
    }

}
