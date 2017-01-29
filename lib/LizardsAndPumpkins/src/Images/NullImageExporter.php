<?php
declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Images;

class NullImageExporter implements ImageExporter
{
    /**
     * @param string $filePath
     */
    public function export($filePath)
    {
        // intentionally left empty
    }
}
