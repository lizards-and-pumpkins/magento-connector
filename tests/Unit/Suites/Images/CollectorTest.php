<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

require_once __DIR__ . '/../RemoveDirectory.php';

use LizardsAndPumpkins\MagentoConnector\RemoveDirectory;

class CollectorTest extends \PHPUnit_Framework_TestCase
{
    use RemoveDirectory;
    /**
     * @var Collector
     */
    private $collector;

    protected function setUp()
    {
        $this->collector = new Collector();
    }

    protected function tearDown()
    {
        $this->removeDirectoryRecursivly($this->testDir);
    }

    public function testAddImageAndReturnIt()
    {
        $image = 'image.png';
        $images = [$image];
        $this->collector->addImage($image);
        $this->assertSame($images, $this->collector->getImages());
    }

    public function testAddTwoImages()
    {
        $image1 = 'image.png';
        $image2 = 'image2.png';
        $images = [$image1, $image2];
        $this->collector->addImage($image1);
        $this->collector->addImage($image2);
        $this->assertSame($images, $this->collector->getImages());
    }

    public function testAddDuplicates()
    {
        $image = 'image.png';
        $images = [$image];
        $this->collector->addImage($image);
        $this->collector->addImage($image);
        $this->assertSame($images, $this->collector->getImages());
    }

    public function testIterator()
    {
        $this->assertInstanceOf(\Iterator::class, $this->collector->getIterator());
    }
}
