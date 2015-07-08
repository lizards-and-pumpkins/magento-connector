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

    /**
     * @param int[] $productIds
     * @param string $action
     */
    public function logProductAction($productIds, $action)
    {
        $productQueue = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueue->saveProductIds($productIds, $action);
    }
}
