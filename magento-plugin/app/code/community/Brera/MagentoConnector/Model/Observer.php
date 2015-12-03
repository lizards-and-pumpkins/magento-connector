<?php

class Brera_MagentoConnector_Model_Observer
{
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductUpdateForProductIds([$productId]);
    }

    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductUpdateForProductIds([$productId]);
    }

    public function catalogProductAttributeUpdateAfter(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getProductIds();
        $this->logProductUpdateForProductIds($productIds);
    }

    public function catalogControllerProductDelete(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductUpdateForProductIds([$productId]);
    }

    public function cataloginventoryStockItemSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->logStockUpdateForProductIds([$productId]);
    }

    public function salesOrderItemCancel(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->logStockUpdateForProductIds([$productId]);
    }

    public function salesModelServiceQuoteSubmitBefore(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'quote');
        $this->logStockUpdateForProductIds($productIds);
    }

    public function salesModelServiceQuoteSubmitFailure(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'quote');
        $this->logStockUpdateForProductIds($productIds);
    }

    public function salesOrderCreditmemoSaveAfter(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'creditmemo');
        $this->logStockUpdateForProductIds($productIds);
    }

    public function cobbyAfterProductImport(Varien_Event_Observer $observer)
    {
        $skus = $observer->getEntities();
        $this->logProductUpdatesForProductSkus($skus);
    }

    public function magmiStockWasUpdated(Varien_Event_Observer $observer)
    {
        $skus = $observer->getSkus();
        $this->logStockUpdatesForProductSkus($skus);
    }

    public function magmiProductsWereUpdated(Varien_Event_Observer $observer)
    {
        $skus = $observer->getSkus();
        $this->logProductUpdatesForProductSkus($skus);
    }

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
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('sku', ['in' => $skus]);
        $this->logProductUpdateForProductIds($collection->getLoadedIds());
    }

    /**
     * @param int[] $ids
     */
    private function logProductUpdateForProductIds(array $ids)
    {
        $helper = Mage::helper('brera_magentoconnector/export');
        $helper->addProductUpdatesToQueue($ids);
    }
}
