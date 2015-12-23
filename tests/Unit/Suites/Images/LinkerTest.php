<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

require_once __DIR__ . '/../RemoveDirectory.php';

use LizardsAndPumpkins\MagentoConnector\RemoveDirectory;

class LinkerTest extends \PHPUnit_Framework_TestCase
{
    use RemoveDirectory;
    /**
     * @var string
     */
    private $targetDir;

    /**
     * @var Linker
     */
    private $linker;

    protected function setUp()
    {
        $this->targetDir = sys_get_temp_dir() . '/' . uniqid() . '/';
        mkdir($this->targetDir);
        $this->linker = Linker::createFor($this->targetDir);
    }

    public function testIsLinker()
    {
        $this->assertInstanceOf(Linker::class, $this->linker);
    }

    public function testDirectoryDoesNotExistThrowsException()
    {
        $targetDirectory = '/this/directory/does/not/exist/';
        $this->setExpectedException(\RuntimeException::class,
            sprintf('Directory "%" does not exist.', $targetDirectory)
        );
        Linker::createFor($targetDirectory);
    }

    public function testSymLink()
    {
        $filename = 'my_original_file';
        $filePath = sys_get_temp_dir() . '/' . $filename;
        touch($filePath);
        $this->linker->link($filePath);
        $this->assertTrue(is_link($this->targetDir . '/' . $filename));
        unlink($filePath);
    }

    public function testExceptionWhenLinkTargetDoesntExist()
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
        $filePath = sys_get_temp_dir() . '/' . $filename;
        touch($filePath);

        $this->linker->link($filePath);
        $this->linker->link($filePath);

        $this->assertTrue(is_link($this->targetDir . '/' . $filename));
        unlink($filePath);
    }

    public function testLinkAlreadyExistsAsFile()
    {
        $this->setExpectedException(\RuntimeException::class);

        $filename = 'my_original_file';
        $filePath = sys_get_temp_dir() . '/' . $filename;
        touch($filePath);
        touch($this->targetDir . '/' . $filename);

        $this->linker->link($filePath);

        unlink($this->targetDir . '/' . $filename);
        unlink($filePath);
    }

    /**
     * @param string $targetDir
     * @dataProvider provideInvalidFileAndDirectoryNames
     */
    public function testInvalidTargetDirectory($targetDir)
    {
        $this->setExpectedException(\RuntimeException::class);
        Linker::createFor($targetDir);
    }

    protected function tearDown()
    {
        $this->removeDirectoryRecursivly($this->testDir);
    }
}
