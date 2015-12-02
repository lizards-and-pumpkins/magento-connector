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
        $builder = $this->getBuilder();
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
     * @depends testValidUrlKey
     * @depends testValidCondition
     */
    public function testValidLocale()
    {
        $builder = ListingBuilder::create('valid-url-key', 'and');

        $this->assertInstanceOf(ListingBuilder::class, $builder);
    }

    private function getBuilder()
    {
        return ListingBuilder::create('valid-url-key', 'and');
    }

}
