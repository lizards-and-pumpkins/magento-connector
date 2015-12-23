<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

use IteratorAggregate;

class Collector implements IteratorAggregate
{
    /**
     * @var string[]
     */
    private $images = [];

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

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getImages());
    }
}
