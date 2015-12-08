<?php

class Brera_MagentoConnector_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogCategorySaveAfter(Varien_Event_Observer $observer)
    {
        Mage::helper('brera_magentoconnector/export')->addCategoryToQueue($observer->getCategory()->getId());
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogCategoryDeleteAfter(Varien_Event_Observer $observer)
    {
        Mage::helper('brera_magentoconnector/export')->addCategoryToQueue($observer->getCategory()->getId());
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogCategoryTreeMoveAfter(Varien_Event_Observer $observer)
    {
        Mage::helper('brera_magentoconnector/export')->addCategoryToQueue($observer->getCategory()->getId());
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductUpdateForProductIds([$productId]);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductUpdateForProductIds([$productId]);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogProductAttributeUpdateAfter(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getProductIds();
        $this->logProductUpdateForProductIds($productIds);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogControllerProductDelete(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductUpdateForProductIds([$productId]);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function cataloginventoryStockItemSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->logStockUpdateForProductIds([$productId]);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderItemCancel(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->logStockUpdateForProductIds([$productId]);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesModelServiceQuoteSubmitBefore(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'quote');
        $this->logStockUpdateForProductIds($productIds);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesModelServiceQuoteSubmitFailure(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'quote');
        $this->logStockUpdateForProductIds($productIds);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderCreditmemoSaveAfter(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'creditmemo');
        $this->logStockUpdateForProductIds($productIds);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function cobbyAfterProductImport(Varien_Event_Observer $observer)
    {
        $skus = $observer->getEntities();
        $this->logProductUpdatesForProductSkus($skus);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function magmiStockWasUpdated(Varien_Event_Observer $observer)
    {
        $skus = $observer->getSkus();
        $this->logStockUpdatesForProductSkus($skus);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function magmiProductsWereUpdated(Varien_Event_Observer $observer)
    {
        $skus = $observer->getSkus();
        $this->logProductUpdatesForProductSkus($skus);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatchCheckoutCartAdd(Varien_Event_Observer $observer)
    {
        $formKey = Mage::getSingleton('core/session')->getFormKey();

        /** @var $request Mage_Core_Controller_Request_Http */
        $request = $observer->getControllerAction()->getRequest();
        $request->setPost('form_key', $formKey);
    }

    /**
     * @param Varien_Event_Observer $observer
     * @param string                $itemHolderName
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
     * @param int[] $ids
     */
    private function logStockUpdateForProductIds(array $ids)
    {
        $this->logProductUpdateForProductIds($ids);
//        $helper = Mage::helper('brera_magentoconnector/export');
//        $helper->addStockUpdatesToQueue($ids);
    }

    /**
     * @param string[] $skus
     */
    private function logStockUpdatesForProductSkus(array $skus)
    {
        $this->logProductUpdatesForProductSkus($skus);
//        $collection = Mage::getResourceModel('catalog/product_collection')
//            ->addAttributeToFilter('sku', ['in' => $skus]);
//        $this->logStockUpdateForProductIds($collection->getLoadedIds());
    }

    /**
     * @param string[] $skus
     */
    private function logProductUpdatesForProductSkus($skus)
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('sku', ['in' => $skus]);
        $this->logProductUpdateForProductIds($collection->getLoadedIds());
    }

    /**
     * @param int[] $ids
     */
    private function logProductUpdateForProductIds(array $ids)
    {
        /** @var Brera_MagentoConnector_Helper_Export $helper */
        $helper = Mage::helper('brera_magentoconnector/export');
        $helper->addProductUpdatesToQueue($ids);
    }
}
