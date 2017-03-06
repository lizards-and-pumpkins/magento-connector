<?php

declare(strict_types = 1);

interface LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector
{
    /**
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory();

    /**
     * @param Mage_Core_Model_Store[] $stores
     */
    public function setStoresToExport(array $stores);
}
