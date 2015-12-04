<?php

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Test_Model_Export_CategoryToLapTransformer
 * @covers \LizardsAndPumpkins\MagentoConnector\XmlBuilder\ListingBuilder
 */
class LizardsAndPumpkins_MagentoConnector_Test_Model_Export_CategoryToLapTransformerTest
    extends PHPUnit_Framework_TestCase
{

    public function testGetXmlWithoutFilter()
    {

        $categoryPath = 'my-category-path';

        $categoryStub = $this->getCategoryStub($categoryPath);

        $transformer = $this->getTransformer($categoryStub);
        $xml = $transformer->getCategoryXml()->getXml();

        $this->assertRegExp("<listing.*?url_key=\"$categoryPath\".*?>", $xml);
        $this->assertRegExp("<listing.*?condition=\"and\".*?>", $xml);
    }

    public function testGetXmlWithAFilterOnCategory()
    {
        $categoryPath = 'my-category-path';

        $categoryStub = $this->getCategoryStub($categoryPath);

        $transformer = $this->getTransformer($categoryStub);
        $xml = $transformer->getCategoryXml()->getXml();

        $this->assertContains("<category operation=\"Equal\">$categoryPath</category>", $xml);
    }

    public function testStoreNotAvailableOnCategory()
    {
        $this->setExpectedException(RuntimeException::class);

        $categoryPath = 'my-category-path';

        /** @var $categoryStub PHPUnit_Framework_MockObject_MockObject|Mage_Catalog_Model_Category */
        $categoryStub = $this->getMockBuilder(Mage_Catalog_Model_Category::class)
            ->setMethods(['getUrlPath', 'getStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $categoryStub->method('getStore')->willReturn(null);
        $categoryStub->method('getUrlPath')->willReturn($categoryPath);

        $transformer = $this->getTransformer($categoryStub);
        $xml = $transformer->getCategoryXml()->getXml();

        $this->assertContains("<category operation=\"Equal\">$categoryPath</category>", $xml);
    }

    public function testGetXmlWithWebsite()
    {
        $categoryPath = 'my-category-path';
        $website = 'ru';
        $categoryStub = $this->getCategoryStub($categoryPath, $website);
        $transformer = $this->getTransformer($categoryStub);
        $xml = $transformer->getCategoryXml()->getXml();

        $this->assertRegExp("<listing.*?website=\"$website\".*?>", $xml);
    }

    public function testGetXmlWithLocale()
    {
        $categoryPath = 'my-category-path';
        $locale = 'ro_RO';
        $categoryStub = $this->getCategoryStub($categoryPath);
        $transformer = $this->getTransformer($categoryStub, $locale);
        $xml = $transformer->getCategoryXml()->getXml();

        $this->assertRegExp("<listing.*?locale=\"$locale\".*?>", $xml);
    }

    public function testGetXmlWithEverything()
    {
        $categoryPath = 'my-category-path';
        $website = 'ru';
        $locale = 'ro_RO';
        $categoryStub = $this->getCategoryStub($categoryPath, $website);
        $transformer = $this->getTransformer($categoryStub, $locale);
        $xml = $transformer->getCategoryXml()->getXml();

        $this->assertRegExp("<listing.*?website=\"$website\".*?>", $xml);
        $this->assertRegExp("<listing.*?locale=\"$locale\".*?>", $xml);
        $this->assertRegExp("<listing.*?url_key=\"$categoryPath\".*?>", $xml);
        $this->assertRegExp("<listing.*?condition=\"and\".*?>", $xml);
        $this->assertContains("<category operation=\"Equal\">$categoryPath</category>", $xml);
    }


    /**
     * @param string $categoryPath
     * @param string $websiteCode
     * @return Mage_Catalog_Model_Category|PHPUnit_Framework_MockObject_MockObject
     */
    private function getCategoryStub($categoryPath, $websiteCode = 'ru')
    {
        $websiteStub = $this->getMock(Mage_Core_Model_Website::class, ['getCode']);
        $websiteStub->method('getCode')->willReturn($websiteCode);

        $storeStub = $this->getMock(Mage_Core_Model_Store::class, ['getWebsite']);
        $storeStub->method('getWebsite')->willReturn($websiteStub);

        $categoryStub = $this->getMockBuilder(Mage_Catalog_Model_Category::class)
            ->setMethods(['getUrlPath', 'getStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $categoryStub->method('getStore')->willReturn($storeStub);

        $categoryStub->method('getUrlPath')->willReturn($categoryPath);
        return $categoryStub;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param string                      $locale
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryToLapTransformer
     */
    private function getTransformer($category, $locale = 'ar_QA')
    {
        $configStub = $this->getMock(LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig::class);
        $configStub->method('getLocaleFrom')->willReturn($locale);
        $transformer =
            new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryToLapTransformer($category, $configStub);

        return $transformer;
    }
}
