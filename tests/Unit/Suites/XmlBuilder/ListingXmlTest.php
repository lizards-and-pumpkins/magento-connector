<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\Exception\StoreNotSetOnCategoryException;
use LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig;
use Mage_Catalog_Model_Category;
use Mage_Core_Model_Store;
use Mage_Core_Model_Website;

/**
 * @covers \LizardsAndPumpkins\MagentoConnector\XmlBuilder\ListingXml
 */
class ListingXmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubConfig;

    /**
     * @var ListingXml
     */
    private $listingXml;

    /**
     * @var Mage_Catalog_Model_Category|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubCategory;

    /**
     * @var Mage_Core_Model_Website|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubWebsite;

    /**
     * @param string $string
     * @return string
     */
    private function removeXmlFormatting($string)
    {
        return preg_replace('/>[^<]+</m', '><', $string);
    }

    protected function setUp()
    {
        $this->stubConfig = $this->getMock(LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig::class);
        $this->listingXml = new ListingXml($this->stubConfig);

        $this->stubWebsite = $this->getMock(Mage_Core_Model_Website::class, [], [], '', false);

        $stubStore = $this->getMock(Mage_Core_Model_Store::class, [], [], '', false);
        $stubStore->method('getWebsite')->willReturn($this->stubWebsite);

        $this->stubCategory = $this->getMock(Mage_Catalog_Model_Category::class, [], [], '', false);
        $this->stubCategory->method('getStore')->willReturn($stubStore);
    }

    public function testExceptionIsThrownIfStoreIsNotSetOnACategory()
    {
        $this->setExpectedException(StoreNotSetOnCategoryException::class);

        /** @var Mage_Catalog_Model_Category|\PHPUnit_Framework_MockObject_MockObject $stubCategory */
        $stubCategory = $this->getMock(Mage_Catalog_Model_Category::class, [], [], '', false);
        $this->listingXml->buildXml($stubCategory);
    }

    public function testXmlStringIsReturned()
    {
        $this->assertInstanceOf(XmlString::class, $this->listingXml->buildXml($this->stubCategory));
    }

    public function testListingNodeContainsUrlKeyAttribute()
    {
        $urlKey = 'foo';
        $this->stubCategory->method('getUrlPath')->willReturn($urlKey);

        $result = $this->listingXml->buildXml($this->stubCategory);

        $this->assertRegExp(sprintf('/<listing [^>]*url_key="%s"/', $urlKey), $result->getXml());
    }

    public function testListingNodeContainsLocaleAttribute()
    {
        $locale = 'foo';
        $this->stubConfig->method('getLocaleFrom')->willReturn($locale);

        $result = $this->listingXml->buildXml($this->stubCategory);

        $this->assertRegExp(sprintf('/<listing [^>]*locale="%"/', $locale), $result->getXml());
    }

    public function testListingNodeContainsWebsiteAttribute()
    {
        $websiteCode = 'foo';
        $this->stubWebsite->method('getCode')->willReturn($websiteCode);

        $result = $this->listingXml->buildXml($this->stubCategory);

        $this->assertRegExp(sprintf('/<listing [^>]*website="%s"/', $websiteCode), $result->getXml());
    }

    public function testListingNodeContainsAndCriteriaNode()
    {
        $result = $this->listingXml->buildXml($this->stubCategory);
        $this->assertContains('<criteria type="and">', $result->getXml());
    }

    public function testListingNodeContainsCategoryCriteria()
    {
        $urlKey = 'foo';
        $this->stubCategory->method('getUrlPath')->willReturn($urlKey);

        $result = $this->listingXml->buildXml($this->stubCategory);
        $expectedXml = sprintf('<attribute name="category" is="Equal">%s</attribute>', $urlKey);

        $this->assertContains($expectedXml, $result->getXml());
    }

    public function testListingNodeContainsStockAvailabilityCriteria()
    {
        $result = $this->listingXml->buildXml($this->stubCategory);

        $expectedXml = <<<EOX
<criteria type="or">
    <attribute name="stock_qty" is="GreaterThan">0</attribute>
    <attribute name="backorders" is="Equal">true</attribute>
</criteria>
EOX;
        $this->assertContains($this->removeXmlFormatting($expectedXml), $this->removeXmlFormatting($result->getXml()));
    }

    public function testListingNodeContainsAttributesNode()
    {
        $result = $this->listingXml->buildXml($this->stubCategory);
        $this->assertContains('<attributes>', $result->getXml());
    }

    public function testAttributesNodeContainsMetaTitleAttribute()
    {
        $attributeCode = 'meta_title';
        $attributeValue = 'foo';

        $this->stubCategory->method('getData')->with($attributeCode)->willReturn($attributeValue);

        $result = $this->listingXml->buildXml($this->stubCategory);
        $expectedRegExp = sprintf(
            '/<attributes>.*<attribute name="%s"><!\[CDATA\[%s\]\]><\/attribute>.*<\/attributes>/s',
            $attributeCode,
            $attributeValue
        );

        $this->assertRegExp($expectedRegExp, $result->getXml());
    }
}
