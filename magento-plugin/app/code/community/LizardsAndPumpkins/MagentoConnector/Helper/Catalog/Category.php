<?php

declare(strict_types = 1);

class LizardsAndPumpkins_MagentoConnector_Helper_Catalog_Category extends Mage_Catalog_Helper_Category
{
    /**
     * @param bool $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return array|Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection|Varien_Data_Tree_Node_Collection
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        $categories = parent::getStoreCategories($sorted, $asCollection, $toLoad);
        $this->deleteCacheBecauseEntriesMightBeInWrongLanguage();

        return $categories;
    }

    private function deleteCacheBecauseEntriesMightBeInWrongLanguage()
    {
        $this->_storeCategories = [];
    }
}
