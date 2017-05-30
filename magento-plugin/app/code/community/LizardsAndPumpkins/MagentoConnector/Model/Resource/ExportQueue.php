<?php

use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue_Message as QueueMessage;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message as QueueMessageResource;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_ProductRelations as ProductRelations;

class LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue
{
    const TYPE_PRODUCT = 'catalog_product';
    const TYPE_CATEGORY = 'catalog_category';

    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    /**
     * @var Varien_Db_Adapter_Interface
     */
    private $connection;

    private $colls = [
        'type'         => QueueMessage::TYPE,
        'data_version' => QueueMessage::DATA_VERSION,
        'object_id'    => QueueMessage::OBJECT_ID,
        'created_at'   => QueueMessage::CREATED_AT,
    ];

    /**
     * @var ProductRelations
     */
    private $productRelations;

    /**
     * @param Mage_Core_Model_Resource $resource
     * @param Varien_Db_Adapter_Interface|null $connection
     * @param ProductRelations $productRelations
     */
    public function __construct(
        $resource = null,
        Varien_Db_Adapter_Interface $connection = null,
        ProductRelations $productRelations = null
    ) {
        $this->resource = $resource ?: Mage::getSingleton('core/resource');
        $this->connection = $connection ?: $this->resource->getConnection('default_write');
        $this->productRelations = $productRelations ?: Mage::getModel('lizardsAndPumpkins_magentoconnector/resource_exportQueue_productRelations');
    }

    /**
     * @param string $targetDataVersion
     */
    public function addAllProductIdsToProductUpdateQueue($targetDataVersion)
    {
        $this->addEntitiesToQueueTable($targetDataVersion, self::TYPE_PRODUCT, $this->productTable());
    }

    /**
     * @param int $websiteId
     * @param string $targetDataVersion
     */
    public function addAllProductIdsFromWebsiteToProductUpdateQueue($websiteId, $targetDataVersion)
    {
        $productToWebsiteTable = $this->resource->getTableName('catalog/product_website');

        $this->addEntitiesToQueueTable(
            $targetDataVersion,
            self::TYPE_PRODUCT,
            $this->productTable(),
            "p2w.website_id = {$websiteId}",
            "INNER JOIN {$productToWebsiteTable} p2w ON {$this->productTable()}.entity_id = p2w.product_id"
        );
    }

    /**
     * @param int[] $productIds
     * @param string $targetDataVersion
     */
    public function addProductUpdatesToQueue(array $productIds, $targetDataVersion)
    {
        $visibleProductIds = $this->productRelations->replaceWithParentProductIds($productIds);
        $condition = $this->connection->quoteInto('entity_id IN (?)', $visibleProductIds);
        $this->addEntitiesToQueueTable($targetDataVersion, self::TYPE_PRODUCT, $this->productTable(), $condition);
    }

    /**
     * @param int $productId
     * @param string $targetDataVersion
     */
    public function addProductUpdateToQueue($productId, $targetDataVersion)
    {
        $this->addProductUpdatesToQueue([$productId], $targetDataVersion);
    }

    /**
     * @param string $targetDataVersion
     */
    public function addAllCategoryIdsToCategoryQueue($targetDataVersion)
    {
        $this->addEntitiesToQueueTable($targetDataVersion, self::TYPE_CATEGORY, $this->categoryTable());
    }

    /**
     * @param int $categoryId
     * @param string $targetDataVersion
     */
    public function addCategoryToQueue($categoryId, $targetDataVersion)
    {
        $condition = $this->connection->quoteInto('entity_id=?', $categoryId);
        $this->addEntitiesToQueueTable($targetDataVersion, self::TYPE_CATEGORY, $this->categoryTable(), $condition);
    }

    /**
     * @param int[] $messageIds
     */
    public function removeMessages(array $messageIds)
    {
        $condition = $this->connection->quoteInto(QueueMessageResource::ID_FIELD . ' IN (?)', $messageIds);
        $this->connection->delete($this->queueTable(), $condition);
    }

    /**
     * @param string $targetDataVersion
     * @param string $queueType
     * @param string $entityTable
     * @param string $condition
     * @param string $join
     */
    private function addEntitiesToQueueTable(
        $targetDataVersion,
        $queueType,
        $entityTable,
        $condition = null,
        $join = null
    ) {
        $join = isset($join) ? $join : '';
        $where = isset($condition) ? 'WHERE ' . $condition : '';

        $query = <<<SQL
INSERT IGNORE INTO `{$this->queueTable()}`
  ({$this->colls['type']}, {$this->colls['data_version']}, {$this->colls['object_id']}, {$this->colls['created_at']})
  (SELECT '{$queueType}', {$this->connection->quote($targetDataVersion)}, entity_id, NOW() FROM `{$entityTable}`
  {$join}
  {$where})
SQL;
        $this->connection->query($query);
    }

    /**
     * @return string
     */
    private function queueTable()
    {
        return $this->resource->getTableName(QueueMessageResource::TABLE);
    }

    /**
     * @return string
     */
    private function productTable()
    {
        return $this->resource->getTableName('catalog/product');
    }

    /**
     * @return string
     */
    private function categoryTable()
    {
        return $this->resource->getTableName('catalog/category');
    }
}
