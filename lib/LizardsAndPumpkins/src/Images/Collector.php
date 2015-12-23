<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

class Collector
{
    /**
     * @var string[]
     */
    private $images;

    /**
     * @param string $image
     */
    public function addImage($image)
    {
        $this->images[$image] = $image;
    }

    /**
     * @return string[]
     */
    public function getImages()
    {
        return array_values($this->images);
    }
}
