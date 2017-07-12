<?php

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory
    implements LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CatalogDataCollectionFactory
{
    /**
     * @param Mage_Core_Model_Store $store
     * @param int[] $entityIdsToExport
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    public function createCollection(Mage_Core_Model_Store $store, array $entityIdsToExport)
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

        $collection->addIdFilter($entityIdsToExport);

        return $collection;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    private function instantiateNewCollection()
    {
        return Mage::getResourceModel('lizardsAndPumpkins_magentoconnector/catalog_product_collection');
    }
}
