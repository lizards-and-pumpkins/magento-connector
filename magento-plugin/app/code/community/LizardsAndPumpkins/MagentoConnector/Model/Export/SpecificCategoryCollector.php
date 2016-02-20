<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_SpecificCategoryCollector
    extends LizardsAndPumpkins_MagentoConnector_Model_Export_AbstractCategoryCollector
{
    /**
     * @var int[]
     */
    private $categoryIdsToExport;
    
    /**
     * @param int[] $categoryIdsToExport
     */
    public function setCategoryIdsToExport(array $categoryIdsToExport)
    {
        $this->categoryIdsToExport = $categoryIdsToExport;
    }
    
    /**
     * @return int[]
     */
    protected function getCategoryIdsToExport()
    {
        $categoryIds = $this->categoryIdsToExport;
        $this->categoryIdsToExport = [];
        return $categoryIds;
    }
}
