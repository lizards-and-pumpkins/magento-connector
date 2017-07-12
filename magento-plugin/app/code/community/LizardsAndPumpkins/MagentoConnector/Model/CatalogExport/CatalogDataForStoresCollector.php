<?php

use \LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CatalogDataCollectionFactory as CatalogDataCollectionFactory;

class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollector
{
    /**
     * @var Mage_Core_Model_Store[]
     */
    private $stores;

    /**
     * @param Mage_Core_Model_Store[] $stores
     */
    public function __construct(array $stores)
    {
        $this->stores = $stores;
    }

    /**
     * @param int[] $idsToExport
     * @param CatalogDataCollectionFactory $collectionFactory
     * @return Generator|Traversable
     */
    public function aggregate(array $idsToExport, CatalogDataCollectionFactory $collectionFactory)
    {
        foreach ($this->stores as $store) {
            $collection = $collectionFactory->createCollection($store, $idsToExport);
            foreach ($collection as $data) {
                yield $data;
            }
        }
    }
}
