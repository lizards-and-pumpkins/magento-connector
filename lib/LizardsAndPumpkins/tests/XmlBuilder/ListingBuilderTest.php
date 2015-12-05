<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class ListingBuilderTest extends \PHPUnit_Framework_TestCase
{
    private function createBuilder($urlKey, $condition)
    {
        return ListingBuilder::create($urlKey, $condition);
    }

    /**
     * @param string $urlKey
     * @dataProvider provideInvalidUrlKey
     */
    public function testExceptionForInvalidUrlKey($urlKey)
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class);
        ListingBuilder::create($urlKey, 'and');
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

        $listingBuilder = ListingBuilder::create($urlKeyWithLeadingSlash, 'and');
        $xml = $listingBuilder->buildXml()->getXml();

        $this->assertNotContains($urlKeyWithLeadingSlash, $xml);
        $this->assertContains($urlKey, $xml);
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

    public function testExceptionForInvalidCondition()
    {
        $condition = 'asdfasdfg';
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Condition must be either "and" or "or"'
        );

        ListingBuilder::create('valid-url-key', $condition);
    }

    /**
     * @param string $locale
     * @dataProvider provideInvalidLocale
     */
    public function testExceptionForInvalidLocale($locale)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $builder = $this->createBuilder('valid-url-key', 'and');
        $builder->setLocale($locale);
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

    public function testUrlInListingXml()
    {
        $listingBuilder = $this->createBuilder('urlkey', 'and');
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

    public function testForExceptionOnInvalidAttribute()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $listingBuilder->addFilterCriterion(new \stdClass(), 'Equal', 'sale');
    }

    public function testExceptionForInvalidOperationOnFilter()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $listingBuilder = $this->createBuilder('urlkey', 'and');
        $listingBuilder->addFilterCriterion('category', new \stdClass(), 'sale');
    }

    public function testExceptionForInvalidAttributeOnFilter()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $listingBuilder = $this->createBuilder('urlkey', 'and');
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
}
