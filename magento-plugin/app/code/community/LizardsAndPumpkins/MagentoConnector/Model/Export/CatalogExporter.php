<?php

use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;

class LizardsAndPumpkins_MagentoConnector_Model_Export_CatalogExporter
{
    const GARBAGE_COLLECT_ALL_N_PRODUCTS = 10000;

    private $numberOfProductsExported = 0;

    private $numberOfCategoriesExported = 0;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Helper_Export
     */
    private $memoizedExportHelper;

    /**
     * @return string
     */
    public function exportAllCategories()
    {
        $helper = $this->getExportHelper();
        $helper->addAllCategoryIdsToCategoryQueue();

        $filename = $this->exportCategoriesInQueue();
        return $filename;
    }

    /**
     * @return string
     */
    public function exportAllProducts()
    {
        $helper = $this->getExportHelper();
        $helper->addAllProductIdsToProductUpdateQueue();

        $filename = $this->exportProductsInQueue();
        return $filename;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    public function exportOneStore(Mage_Core_Model_Store $store)
    {
        $helper = $this->getExportHelper();
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($store->getWebsite());

        $collector = $this->createProductCollector();

        $collector->setStoresToExport([$store]);

        $filename = $this->export($collector);
        return $filename;
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return int
     */
    public function exportOneWebsite(Mage_Core_Model_Website $website)
    {
        $helper = $this->getExportHelper();
        $helper->addAllProductIdsFromWebsiteToProductUpdateQueue($website);

        $collector = $this->createProductCollector();

        $collector->setStoresToExport($website->getStores());

        $filename = $this->export($collector);
        return $filename;
    }

    /**
     * @return string
     */
    public function exportProductsInQueue()
    {
        $collector = $this->createProductCollector();
        $filename = $this->export($collector);
        return $filename;
    }

    /**
     * @return string
     */
    public function exportCategoriesInQueue()
    {
        $xmlMerge = new CatalogMerge();
        $config = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_magentoConfig');
        $categoryCollector = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector($config);
        $uploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();
        while ($category = $categoryCollector->getCategory()) {
            $transformer = LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryTransformer::createFrom($category);
            $categoryXml = $transformer->getCategoryXml();
            $xmlMerge->addCategory($categoryXml);
            $this->numberOfCategoriesExported++;
        }

        if ($this->numberOfProductsExported + $this->numberOfCategoriesExported === 0) {
            return $uploader->getFilename();
        }

        $uploader->writePartialXmlString($xmlMerge->finish());
        $filename = $uploader->getFilename();
        return $filename;
    }

    /**
     * @param LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector
     * @return string
     */
    private function export(LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector $collector)
    {
        $xmlMerge = new CatalogMerge();
        $uploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();
        $resource = Mage::getSingleton('core/resource');
        $config = Mage::getModel('lizardsAndPumpkins_magentoconnector/export_magentoConfig');
        $sourceTableAttributeData = new LizardsAndPumpkins_MagentoConnector_Model_Export_SourceTableDataProvider(
            $resource,
            $config
        );
        while ($product = $collector->getProduct()) {
            $xmlBuilderAndUploader = new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductXmlBuilderAndUploader(
                $product,
                $xmlMerge,
                $uploader,
                $sourceTableAttributeData
            );

            $xmlBuilderAndUploader->process();
            if ($this->numberOfProductsExported % self::GARBAGE_COLLECT_ALL_N_PRODUCTS === 0) {
                gc_collect_cycles();
            }
            $this->numberOfProductsExported++;
        }

        if ($this->numberOfProductsExported + $this->numberOfCategoriesExported === 0) {
            return $uploader->getFilename();
        }

        $uploader->writePartialXmlString($xmlMerge->finish());
        return $uploader->getFilename();
    }

    /**
     * @return int
     */
    public function getNumberOfCategoriesExported()
    {
        return $this->numberOfCategoriesExported;
    }

    /**
     * @return int
     */
    public function getNumberOfProductsExported()
    {
        return $this->numberOfProductsExported;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Helper_Export
     */
    private function getExportHelper()
    {
        if (null === $this->memoizedExportHelper) {
            $this->memoizedExportHelper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        }

        return $this->memoizedExportHelper;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    private function createProductCollector()
    {
        return Mage::getModel('lizardsAndPumpkins_magentoconnector/export_productCollector');
    }
}
