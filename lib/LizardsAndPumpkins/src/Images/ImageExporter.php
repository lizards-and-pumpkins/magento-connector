<?php
declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Images;

interface ImageExporter
{
    /**
     * @param string $filePath
     */
    public function export($filePath);
}
