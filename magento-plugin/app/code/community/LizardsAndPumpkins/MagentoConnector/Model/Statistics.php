<?php

declare(strict_types = 1);

class LizardsAndPumpkins_MagentoConnector_Model_Statistics
{
    /**
     * @var Mage_Core_Model_Resource
     */
    private $resource;

    public function __construct(Mage_Core_Model_Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return int
     */
    public function getQueuedCategoriesCount()
    {
        $queueName = LizardsAndPumpkins_MagentoConnector_Helper_Export::QUEUE_CATEGORY_UPDATES;
        return $this->getMessageCountFor($queueName);
    }

    /**
     * @return int
     */
    public function getQueuedProductCount()
    {
        $queue = LizardsAndPumpkins_MagentoConnector_Helper_Export::QUEUE_PRODUCT_UPDATES;
        return $this->getMessageCountFor($queue);
    }

    /**
     * @param $queue
     * @return string
     */
    private function getMessageCountFor($queue)
    {
        $queueId = Mage::helper('lizardsAndPumpkins_magentoconnector/export')->getQueueIdByName($queue);
        $result = $this->resource->getConnection('core_write')->query(
            "SELECT COUNT(*) FROM message WHERE queue_id = :queueId",
            [':queueId' => $queueId]
        );
        return $result->fetchColumn();
    }
}
