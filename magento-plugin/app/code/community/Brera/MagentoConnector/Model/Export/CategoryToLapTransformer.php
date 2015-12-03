<?php

use Brera\MagentoConnector\XmlBuilder\ListingBuilder;

class Brera_MagentoConnector_Model_Export_CategoryToLapTransformer
{
    /**
     * @var Mage_Catalog_Model_Category
     */
    private $category;

    /**
     * @param Mage_Catalog_Model_Category $category
     */
    public function __construct(Mage_Catalog_Model_Category $category)
    {
        $this->category = $category;
    }

    /**
     * @return \Brera\MagentoConnector\XmlBuilder\XmlString
     */
    public function getCategoryXml()
    {
        if (!($this->category->getStore() instanceof Mage_Core_Model_Store)) {
            throw new RuntimeException('Store must be set on category.');
        }

        $urlPath = $this->category->getUrlPath();
        $listingBuilder = ListingBuilder::create($urlPath, 'and');
        $listingBuilder->addFilterCriterion('category', 'Equal', $urlPath);
        $listingBuilder->setLocale($this->getLocale($this->category->getStore()));
        $listingBuilder->setWebsite($this->category->getStore()->getWebsite()->getCode());

        return $listingBuilder->buildXml();
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    protected function getLocale($store)
    {
        return Mage::getStoreConfig('general/locale/code', $store);
    }
}
