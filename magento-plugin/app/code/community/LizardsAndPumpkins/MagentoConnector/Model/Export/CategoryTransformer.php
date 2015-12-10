<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\ListingBuilder;

class LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer
{
    /**
     * @var Mage_Catalog_Model_Category
     */
    private $category;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    /**
     * @param Mage_Catalog_Model_Category                                    $category
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config
     */
    private function __construct(
        Mage_Catalog_Model_Category $category,
        LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config = null
    ) {
        $this->category = $category;
        $this->config = $config;
    }

    /**
     * @param Mage_Catalog_Model_Category                                         $category
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig|null $config
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer
     */
    public static function createForTesting(
        Mage_Catalog_Model_Category $category,
        LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig $config = null
    ) {
        return new self($category, $config);
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer
     */
    public static function createFrom(Mage_Catalog_Model_Category $category)
    {
        $config = new LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig();
        return new self($category, $config);
    }

    /**
     * @return \LizardsAndPumpkins\MagentoConnector\XmlBuilder\XmlString
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
    private function getLocale($store)
    {
        return $this->config->getLocaleFrom($store);
    }
}
