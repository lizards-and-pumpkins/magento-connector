<?php
namespace Brera\MagentoConnector\XmlBuilder;

class ListingBuilderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param string $urlKey
     * @dataProvider provideInvalidUrlKey
     */
    public function testInvalidUrlKey($urlKey)
    {
        $this->setExpectedExceptionRegExp(
            \InvalidArgumentException::class,
            '#Only a-z A-Z 0-9 and "\$-_\.\+!\*\'\(\)," are allowed for a url.*#'
        );
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

    private function createBuilder($urlKey = 'valid-url-key', $condition = 'and')
    {
        return ListingBuilder::create($urlKey, $condition);
    }

}
