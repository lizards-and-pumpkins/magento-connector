<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

require_once __DIR__ . '/../RemoveDirectory.php';

use LizardsAndPumpkins\MagentoConnector\RemoveDirectory;

class ImageLinkerTest extends \PHPUnit_Framework_TestCase
{
    use RemoveDirectory;

    /**
     * @var string
     */
    private $testDir;

    /**
     * @var string
     */
    private $targetDir;

    /**
     * @var ImageLinker
     */
    private $linker;

    protected function setUp()
    {
        $this->testDir = sys_get_temp_dir() . '/' . uniqid() . '/';
        $this->targetDir = $this->testDir . 'targetDir/';
        mkdir($this->targetDir, 0777, true);
        $this->linker = ImageLinker::createFor($this->targetDir);
    }

    protected function tearDown()
    {
        $this->removeDirectoryRecursivly($this->testDir);
    }

    public function testIsLinker()
    {
        $this->assertInstanceOf(ImageLinker::class, $this->linker);
    }

    public function testDirectoryDoesNotExistThrowsException()
    {
        $targetDirectory = '/this/directory/does/not/exist/';
        $this->setExpectedException(\RuntimeException::class,
            sprintf('Directory "%" does not exist.', $targetDirectory)
        );
        ImageLinker::createFor($targetDirectory);
    }

    public function testSymLink()
    {
        $filename = 'my_original_file';
        $filePath = $this->testDir . $filename;
        touch($filePath);
        $this->linker->link($filePath);
        $this->assertTrue(is_link($this->targetDir . '/' . $filename));
        unlink($filePath);
    }

    public function testExceptionWhenLinkTargetDoesNotExist()
    {
        $target = '/file/does/not/exist';
        $this->setExpectedException(
            \RuntimeException::class,
            sprintf('Link target "%s" does not exist.', $target)
        );

        $this->linker->link($target);
    }

    /**
     * @param mixed $invalidLinkTarget
     * @dataProvider provideInvalidFileAndDirectoryNames
     */
    public function testInvalidLinkTargets($invalidLinkTarget)
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->linker->link($invalidLinkTarget);
    }

    /**
     * @return mixed[]
     */
    public function provideInvalidFileAndDirectoryNames()
    {
        return [
            [42],
            [M_PI],
            [new \stdClass()],
            [[]],
        ];
    }

    public function testIgnoreIfLinkAlreadyExists()
    {
        $filename = 'my_original_file';
        $filePath = $this->testDir . $filename;
        touch($filePath);

        $this->linker->link($filePath);
        $this->linker->link($filePath);

        $this->assertTrue(is_link($this->targetDir . '/' . $filename));
    }

    public function testLinkAlreadyExistsAsFile()
    {
        $this->setExpectedException(\RuntimeException::class);

        $filename = 'my_original_file';
        $filePath = $this->testDir . $filename;
        touch($filePath);
        touch($this->targetDir . '/' . $filename);

        $this->linker->link($filePath);
    }

    /**
     * @param string $targetDir
     * @dataProvider provideInvalidFileAndDirectoryNames
     */
    public function testInvalidTargetDirectory($targetDir)
    {
        $this->setExpectedException(\RuntimeException::class);
        ImageLinker::createFor($targetDir);
    }
}
