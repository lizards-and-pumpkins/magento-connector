<?php

declare(strict_types = 1);

class LizardsAndPumpkins_MagentoConnector_Model_Export_CategoriesInQueueCollector
    extends LizardsAndPumpkins_MagentoConnector_Model_Export_AbstractCategoryCollector
{

    /**
     * @var Zend_Queue_Message_Iterator
     */
    private $messageIterator;
    
    /**
     * @return int[]
     */
    final protected function getCategoryIdsToExport()
    {
        return $this->getQueuedCategoryIds();
    }

    /**
     * @return int[]
     */
    private function getQueuedCategoryIds()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_Export $exportHelper */
        $exportHelper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');

        $this->messageIterator = $exportHelper->getCategoryUpdatesToExport();
        $categoryIds = [];
        foreach ($this->messageIterator as $item) {
            /** @var $item Zend_Queue_Message */
            $categoryIds[] = $item->body;
        }
        if ($categoryIds) {
            $this->deleteMessages();
        }

        return $categoryIds;
    }

    private function deleteMessages()
    {
        $ids = [];
        foreach ($this->messageIterator as $message) {
            $ids[] = (int) $message->message_id;
        }

        $ids = implode(',', $ids);

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $resource->getConnection('core_write')->delete('message', "message_id IN ($ids)");
    }
}
