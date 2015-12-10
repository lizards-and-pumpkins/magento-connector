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
     * @return $this
     */
    protected function _afterLoad()
    {
        if ($this->_addUrlRewrite) {
            $this->_addUrlRewrite($this->_urlRewriteCategory);
        }

        if (count($this) > 0) {
            Mage::dispatchEvent('catalog_product_collection_load_after', ['collection' => $this]);
        }

        foreach ($this as $product) {
            /** @var $product Mage_Catalog_Model_Product */
            if ($product->isRecurring() && $profile = $product->getRecurringProfile()) {
                $product->setRecurringProfile(unserialize($profile));
            }
            $product->setStore($this->getStoreId());
        }

        return $this;
    }

}
