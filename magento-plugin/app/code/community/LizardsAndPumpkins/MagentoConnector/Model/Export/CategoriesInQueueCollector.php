<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_CategoriesInQueueCollector
    extends LizardsAndPumpkins_MagentoConnector_Model_Export_AbstractCategoryCollector
{

    /**
     * @var Zend_Queue_Message_Iterator
     */
    private $messageIterator;

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection[]
     */
    final protected function getCategoriesToExportGroupedByDataVersion()
    {
        $exportQueue = Mage::helper('lizardsAndPumpkins_magentoconnector/factory')->createExportQueue();

        return $exportQueue->getQueuedCategoryUpdatesGroupedByDataVersion();
    }
}
