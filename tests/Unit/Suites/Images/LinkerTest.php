<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

class LinkerTest extends \PHPUnit_Framework_TestCase
{
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
        if (!is_dir($this->targetDir)) {
            return;
        }
        $dir = $this->targetDir;
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir((string) $file);
            } else {
                unlink((string) $file);
            }
        }
        rmdir($dir);
    }
}
