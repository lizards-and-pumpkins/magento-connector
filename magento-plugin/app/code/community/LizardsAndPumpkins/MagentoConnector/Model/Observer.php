<?php

class LizardsAndPumpkins_MagentoConnector_Model_Observer
{
    public function catalogCategorySaveCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = $observer->getData('category');
        
        $this->getExportQueue()->addCategoryToQueue($category->getId(), $this->getTargetVersion());
    }

    public function catalogCategoryDeleteCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = $observer->getData('category');
        
        $this->getExportQueue()->addCategoryToQueue($category->getId(), $this->getTargetVersion());
    }

    public function catalogCategoryTreeMoveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = $observer->getData('category');

        $this->getExportQueue()->addCategoryToQueue($category->getId(), $this->getTargetVersion());
    }

    public function catalogProductSaveCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        $this->addProductToExportQueueByIds([$product->getId()]);
    }

    public function catalogProductDeleteCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        $this->addProductToExportQueueByIds([$product->getId()]);
    }

    public function catalogProductAttributeUpdateAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        $this->addProductToExportQueueByIds([$product->getId()]);
    }

    public function catalogControllerProductDelete(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        $this->addProductToExportQueueByIds([$product->getId()]);
    }

    public function cataloginventoryStockItemSaveCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        $this->addProductToExportQueueByIds([$product->getId()]);
    }

    public function salesOrderItemCancel(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        $this->addProductToExportQueueByIds([$product->getId()]);
    }

    public function salesModelServiceQuoteSubmitAfter(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'quote');
        $this->addProductToExportQueueByIds($productIds);
    }

    public function salesOrderCreditmemoSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'creditmemo');
        $this->addProductToExportQueueByIds($productIds);
    }

    public function controllerActionPredispatchCheckoutCartAdd(Varien_Event_Observer $observer)
    {
        $formKey = Mage::getSingleton('core/session')->getFormKey();

        /** @var Mage_Core_Controller_Front_Action $action */
        $action = $observer->getData('controller_action');
        $request = $action->getRequest();
        $request->setPost('form_key', $formKey);
    }

    /**
     * @param Varien_Event_Observer $observer
     * @param string $itemHolderName
     * @return int[]
     */
    private function getProductIdsFrom(Varien_Event_Observer $observer, $itemHolderName)
    {
        $itemHolder = $observer->getDataUsingMethod($itemHolderName);
        return array_map(function (Varien_Object $itemHolder) {
            return $itemHolder->getData('product_id');
        }, $itemHolder->getAllItems());
    }

    /**
     * @param int[] $productIds
     */
    private function addProductToExportQueueByIds(array $productIds)
    {
        $this->getExportQueue()->addProductUpdatesToQueue($productIds, $this->getTargetVersion());
    }
    
    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_ExportQueue
     */
    private function getExportQueue()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_Factory $factory */
        $factory = Mage::helper('lizardsAndPumpkins_magentoconnector/factory');

        return $factory->createExportQueue();
    }

    private function getTargetVersion()
    {
        /** @var LizardsAndPumpkins_MagentoConnector_Helper_DataVersion $dataVersionHelper */
        $dataVersionHelper = Mage::helper('lizardsAndPumpkins_magentoconnector/dataVersion');

        return $dataVersionHelper->getTargetVersion();
    }
}
