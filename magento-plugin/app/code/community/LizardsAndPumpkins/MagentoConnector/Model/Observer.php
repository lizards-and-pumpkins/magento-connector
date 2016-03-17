<?php

class LizardsAndPumpkins_MagentoConnector_Model_Observer
{
    public function catalogCategorySaveCommitAfter(Varien_Event_Observer $observer)
    {
        $this->getExportHelper()->addCategoryToQueue($observer->getCategory()->getId());
    }

    public function catalogCategoryDeleteCommitAfter(Varien_Event_Observer $observer)
    {
        $this->getExportHelper()->addCategoryToQueue($observer->getCategory()->getId());
    }

    public function catalogCategoryTreeMoveAfter(Varien_Event_Observer $observer)
    {
        $this->getExportHelper()->addCategoryToQueue($observer->getCategory()->getId());
    }

    public function catalogProductSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->addProductToExportQueueByIds([$productId]);
    }

    public function catalogProductDeleteCommitAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->addProductToExportQueueByIds([$productId]);
    }

    public function catalogProductAttributeUpdateAfter(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getProductIds();
        $this->addProductToExportQueueByIds($productIds);
    }

    public function catalogControllerProductDelete(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProduct()->getId();
        $this->addProductToExportQueueByIds([$productId]);
    }

    public function cataloginventoryStockItemSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->addProductToExportQueueByIds([$productId]);
    }

    public function salesOrderItemCancel(Varien_Event_Observer $observer)
    {
        $productId = $observer->getItem()->getProductId();
        $this->addProductToExportQueueByIds([$productId]);
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

    public function cobbyAfterProductImport(Varien_Event_Observer $observer)
    {
        $skus = array_keys($observer->getData('entities'));
        $this->addProductToExportQueueBySkus($skus);
    }

    public function cobbyAfterProductStockImport(Varien_Event_Observer $observer)
    {
        $this->addProductToExportQueueByIds($observer->getData('products'));
    }

    public function cobbyAfterProductCategoryImport(Varien_Event_Observer $observer)
    {
        $this->addProductToExportQueueByIds($observer->getData('products'));
    }

    public function cobbyAfterProductMediaImport(Varien_Event_Observer $observer)
    {
        $this->addProductToExportQueueByIds($observer->getData('products'));
    }

    public function cobbyAfterProductUrlImport(Varien_Event_Observer $observer)
    {
        $this->addProductToExportQueueByIds($observer->getData('products'));
    }

    public function cobbyAfterProductConfigurableImport(Varien_Event_Observer $observer)
    {
        $this->addProductToExportQueueByIds($observer->getData('products'));
    }

    public function magmiStockWasUpdated(Varien_Event_Observer $observer)
    {
        $skus = $observer->getSkus();
        $this->addProductToExportQueueBySkus($skus);
    }

    public function magmiProductsWereUpdated(Varien_Event_Observer $observer)
    {
        $skus = $observer->getSkus();
        $this->addProductToExportQueueBySkus($skus);
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
     * @param string[] $skus
     */
    private function addProductToExportQueueBySkus(array $skus)
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('sku', ['in' => $skus])
            ->load();

        $this->addProductToExportQueueByIds($collection->getLoadedIds());
    }

    /**
     * @param int[] $ids
     */
    private function addProductToExportQueueByIds(array $ids)
    {
        $visibleProductIds = $this->replaceChildProductIdsWithParentProductIds($ids);
        $this->getExportHelper()->addProductUpdatesToQueue($visibleProductIds);
    }

    /**
     * @param int[] $ids
     * @return string[]
     */
    private function replaceChildProductIdsWithParentProductIds(array $ids)
    {
        $relations = $this->getRelationsOfGivenIds($ids);

        return array_map(function ($productId) use ($relations) {
            if (isset($relations[$productId])) {
                return $relations[$productId];
            }

            return $productId;
        }, $ids);
    }

    /**
     * @param int[] $productIds
     * @return string[]
     */
    private function getRelationsOfGivenIds(array $productIds)
    {
        $query = 'SELECT `product_id`, `parent_id`
                    FROM `catalog_product_super_link`
                   WHERE `product_id` IN (' . implode(',', $productIds) . ')';

        return $this->getReadConnection()->fetchPairs($query);
    }

    /**
     * @return Varien_Db_Adapter_Interface
     */
    private function getReadConnection()
    {
        return $this->getResource()->getConnection('core_read');
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    private function getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Helper_Export
     */
    private function getExportHelper()
    {
        return Mage::helper('lizardsAndPumpkins_magentoconnector/export');
    }
}
