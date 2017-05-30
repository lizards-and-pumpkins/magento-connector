<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_ProductRelations
{
    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @var Varien_Db_Adapter_Interface
     */
    private $connection;

    /**
     * @param Mage_Core_Model_Resource $resource
     * @param Varien_Db_Adapter_Interface|null $connection
     */
    public function __construct($resource = null, Varien_Db_Adapter_Interface $connection = null)
    {
        $this->resource = $resource ?: Mage::getSingleton('core/resource');
        $this->connection = $connection ?: $this->resource->getConnection('default_write');
    }

    public function replaceWithParentProductIds(array $productIds)
    {
        $childToParentMap = $this->getChildToParentIdMapOfGivenIds($productIds);
        return array_map(function ($productId) use ($childToParentMap) {
            return isset($childToParentMap[$productId]) ? $childToParentMap[$productId] : $productId; 
        }, $productIds);
    }

    private function getChildToParentIdMapOfGivenIds(array $productIds)
    {
        $tableName = $this->resource->getTableName('catalog/product_super_link');
        $condition = $this->connection->quoteInto('product_id IN (?)', $productIds);
        $query = "SELECT `product_id`, `parent_id`
                    FROM `{$tableName}`
                   WHERE {$condition}";

        return $this->connection->fetchPairs($query);
    }
}
