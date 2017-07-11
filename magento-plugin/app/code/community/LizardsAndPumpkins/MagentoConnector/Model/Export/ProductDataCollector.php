<?php

/**
 * @deprecated 
 * @see \LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory
 */
class LizardsAndPumpkins_MagentoConnector_Model_Export_ProductDataCollector implements \IteratorAggregate
{
    /**
     * @var int[]
     */
    private $productIdsToExport;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $limitExportToStores;

    /**
     * @param int[] $productIdsToExport
     * @param Mage_Core_Model_Store[] $storesToExport
     */
    public function __construct(array $productIdsToExport, array $storesToExport)
    {
        $this->productIdsToExport = $productIdsToExport;
        $this->limitExportToStores = $storesToExport;
    }

    /**
     * @return Generator
     */
    public function getIterator()
    {
        foreach ($this->limitExportToStores as $store) {
            $productsCollection = $this->createCollection($store);
            foreach ($productsCollection as $productData) {
                yield $productData;
            }
        }
    }
    
    /**
     * @param Mage_Core_Model_Store $store
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    private function createCollection(Mage_Core_Model_Store $store)
    {
        $collection = $this->instantiateNewCollection();
        $collection->setFlag(
            LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection::FLAG_LOAD_ASSOCIATED_PRODUCTS,
            true
        );
        $collection->setFlag(
            LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection::FLAG_ADD_CATEGORY_IDS,
            true
        );
        $collection->setStore($store);
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter(
            'visibility',
            ['neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE]
        );
        $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addWebsiteFilter($store->getWebsiteId());

        $collection->addIdFilter($this->productIdsToExport);
        
        return $collection;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    protected function instantiateNewCollection()
    {
        return Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/catalog_product_collection');
    }
}
