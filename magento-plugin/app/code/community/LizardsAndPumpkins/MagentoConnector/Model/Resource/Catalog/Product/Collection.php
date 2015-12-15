<?php

class LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
    extends Mage_Catalog_Model_Resource_Product_Collection
{
    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Product_Collection
     */
    protected function _afterLoad()
    {
        if ($this->_addUrlRewrite) {
            $this->_addUrlRewrite();
        }

        if (count($this) > 0) {
            Mage::dispatchEvent('catalog_product_collection_load_after', ['collection' => $this]);
        }

        array_map(function (Mage_Catalog_Model_Product $product) {
            if ($product->isRecurring() && $profile = $product->getData('recurring_profile')) {
                $product->setData('recurring_profile', unserialize($profile));
            }
            $product->setData('store_id', $this->getStoreId());
        }, $this->getItems());

        return $this;
    }
}
