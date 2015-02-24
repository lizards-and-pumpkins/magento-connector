<?php

class Brera_MagentoConnector_Model_Observer
{
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $productQueue = Mage::getModel('brera_magentoconnector/product_queue_item');
        $productQueue->setProductId($productId)->save();
    }
}