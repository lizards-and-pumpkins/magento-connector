<?php

class Brera_MagentoConnector_Model_Observer
{
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductAction(
            [$productId],
            Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE
        );
    }

    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductAction([$productId], Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE);
    }

    public function catalogProductAttributeUpdateAfter(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getProductIds();
        $this->logProductAction($productIds, Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE);
    }

    public function catalogControllerProductDelete(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->logProductAction([$productId], Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE);
    }

    public function cataloginventoryStockItemSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->logProductAction([$productId], Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE);
    }

    public function salesOrderItemCancel(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->logProductAction(
            [$productId], Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE
        );
    }

    public function salesModelServiceQuoteSubmitBefore(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'quote');
        $this->logProductAction($productIds, Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE);
    }

    public function salesModelServiceQuoteSubmitFailure(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'quote');
        $this->logProductAction($productIds, Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE);
    }

    public function salesOrderCreditmemoSaveAfter(Varien_Event_Observer $observer)
    {
        $productIds = $this->getProductIdsFrom($observer, 'creditmemo');
        $this->logProductAction($productIds, Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE);
    }

    /**
     * @param Varien_Event_Observer $observer
     * @param string $itemHolder
     * @return int[]
     */
    private function getProductIdsFrom(Varien_Event_Observer $observer, $itemHolder)
    {
        $itemHolder = $observer->getDataUsingMethod($itemHolder);
        $productIds = [];
        foreach ($itemHolder->getAllItems() as $item) {
            $productIds[] = $item->getProductId();
        }

        return $productIds;
    }

    /**
     * @param int[] $productIds
     * @param string $action
     */
    private function logProductAction($productIds, $action)
    {
        $productQueue = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueue->saveProductIds($productIds, $action);
    }
}
