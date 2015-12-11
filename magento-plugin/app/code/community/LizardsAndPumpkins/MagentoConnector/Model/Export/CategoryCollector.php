<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector
{
    /**
     * @var Mage_Catalog_Model_Resource_Category_Collection
     */
    private $collection;

    /**
     * @var ArrayIterator
     */
    private $categoryIterator;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $storesToExportTemplate;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $storesToExportInCurrentLoop;

    /**
     * @var Mage_Core_Model_Store
     */
    private $store;

    /**
     * @var int[]
     */
    private $queuedCategoryIds;

    /**
     * @var Zend_Queue_Message_Iterator
     */
    private $messageIterator;

    /**
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory()
    {
        if ($this->existsNextCategory()) {
            return $this->categoryIterator->current();
        }

        $this->prepareNextBunchOfCategories();

        $this->setNextStoreToExport();
        if (!$this->isCategoryLeftForExport()) {
            return null;
        }

        $this->createCollectionWithIdFilter();

        $this->categoryIterator = $this->collection->getIterator();
        if ($this->categoryIterator->current() === null) {
            return $this->getCategory();
        }
        return $this->categoryIterator->current();
    }

    /**
     * @return bool
     */
    private function existsNextCategory()
    {
        if ($this->categoryIterator) {
            $this->categoryIterator->next();
            return $this->categoryIterator->valid();
        }
        return false;
    }

    private function prepareNextBunchOfCategories()
    {
        if (empty($this->storesToExportInCurrentLoop)) {
            $this->storesToExportInCurrentLoop = $this->getStoresToExport();
            $this->queuedCategoryIds = $this->getQueuedCategoryIds();
        }
    }

    /**
     * @return Mage_Core_Model_Store[]
     */
    private function getStoresToExport()
    {
        if (!$this->storesToExportTemplate) {
            return Mage::app()->getStores();
        }
        return $this->storesToExportTemplate;
    }

    /**
     * @param Mage_Core_Model_Store[] $stores
     */
    public function setStoresToExport(array $stores)
    {
        $this->storesToExportTemplate = $stores;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Resource_Category_Collection
     */
    private function createCollection(Mage_Core_Model_Store $store)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Category_Collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->setStore($store);
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('is_active', 1);
        return $collection;
    }

    /**
     * @return int[]
     */
    private function getQueuedCategoryIds()
    {
        $this->messageIterator = Mage::helper('lizardsAndPumpkins_magentoconnector/export')
            ->getCategoryUpdatesToExport();
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
        $resouce = Mage::getSingleton('core/resource');
        $resouce->getConnection('core_write')->delete('message', "message_id IN ($ids)");
    }

    private function setNextStoreToExport()
    {
        $this->store = array_pop($this->storesToExportInCurrentLoop);
    }

    /**
     * @return bool
     */
    private function isCategoryLeftForExport()
    {
        return !empty($this->queuedCategoryIds);
    }

    private function createCollectionWithIdFilter()
    {
        $this->collection = $this->createCollection($this->store);
        $this->collection->addIdFilter($this->queuedCategoryIds);
    }
}
