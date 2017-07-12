<?php

interface LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CatalogDataCollectionFactory
{
    /**
     * @param Mage_Core_Model_Store $store
     * @param int[] $entityIdsToExport
     * @return Mage_Catalog_Model_Resource_Collection_Abstract
     */
    public function createCollection(Mage_Core_Model_Store $store, array $entityIdsToExport);
}
