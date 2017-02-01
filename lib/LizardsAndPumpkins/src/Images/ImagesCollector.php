<?php
declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Images;

class ImagesCollector implements \IteratorAggregate
{
    /**
     * @var string[]
     */
    private $images = [];

    /**
     * @param string $imageFilePath
     */
    public function addImage($imageFilePath)
    {
        $this->validateImageFile($imageFilePath);
        $this->images[$imageFilePath] = $imageFilePath;
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

    /**
     * @param string $imageFilePath
     */
    private function validateImageFile($imageFilePath)
    {
        if (!is_string($imageFilePath)) {
            throw new \InvalidArgumentException('Image file path must be string.');
        }
        if (!is_file($imageFilePath)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $imageFilePath));
        }
    }
}
