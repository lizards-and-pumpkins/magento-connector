<?php
declare(strict_types=1);

interface LizardsAndPumpkins_MagentoConnector_Helper_ProductsToUpdateQueueReader
{
    /**
     * @return int[]
     */
    public function getQueuedProductIds();
}
