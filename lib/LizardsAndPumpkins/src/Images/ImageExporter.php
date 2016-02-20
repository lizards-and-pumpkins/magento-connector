<?php
namespace LizardsAndPumpkins\MagentoConnector\Images;

interface ImageExporter
{
    /**
     * @param string $filePath
     */
    public function export($filePath);
}
