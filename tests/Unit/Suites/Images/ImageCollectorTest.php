<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

require_once __DIR__ . '/../RemoveDirectory.php';

use LizardsAndPumpkins\MagentoConnector\RemoveDirectory;

class ImageCollectorTest extends \PHPUnit_Framework_TestCase
{
    use RemoveDirectory;

    /**
     * @var string
     */
    private $testDir;

    /**
     * @var ImagesCollector
     */
    private $collector;

    /**
     * @var string
     */
    private $image1;

    /**
     * @var string
     */
    private $image2 = 'image2.png';

    protected function setUp()
    {
        $this->collector = new ImagesCollector();
        $this->testDir = sys_get_temp_dir() . '/' . uniqid() . '/';
        mkdir($this->testDir, 0777, true);

        $this->image1 = $this->testDir . 'image.png';
        $this->image2 = $this->testDir . 'image2.png';

        touch($this->image1);
        touch($this->image2);
    }

    protected function tearDown()
    {
        $this->removeDirectoryRecursivly($this->testDir);
    }

    public function testAddImageAndReturnIt()
    {
        $this->collector->addImage($this->image1);
        $this->assertSame([$this->image1], $this->collector->getImages());
    }

    public function testAddTwoImages()
    {
        $this->collector->addImage($this->image1);
        $this->collector->addImage($this->image2);
        $this->assertSame([$this->image1, $this->image2], $this->collector->getImages());
    }

    public function testAddDuplicates()
    {
        $this->collector->addImage($this->image1);
        $this->collector->addImage($this->image1);

        $this->assertSame([$this->image1], $this->collector->getImages());
    }

    public function testImplementsIteratorInterface()
    {
        $this->assertInstanceOf(\Iterator::class, $this->collector->getIterator());
    }
}
