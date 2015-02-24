<?php

class Brera_MagentoConnector_Model_Observer
{
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $productQueue = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueue->setAction(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE)
            ->setProductId($productId)
            ->save();
    }

    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        $this->logDeletedProduct($observer);
    }

    public function catalogProductAttributeUpdateAfter(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getProductIds();
        $productQueue = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueue->saveProductIds(
            $productIds,
            Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_CREATE_AND_UPDATE
        );
    }
    /**
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    private function logDeletedProduct(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $productQueue = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueue->setAction(Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_DELETE)
            ->setProductId($productId)
            ->save();
    }
}
