<?php

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
