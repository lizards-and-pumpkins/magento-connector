<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

class Linker
{
    /**
     * @var string
     */
    private $targetDir;

    /**
     * @param string $targetDir
     */
    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * @param string $targetDir
     * @return Linker
     */
    public static function createFor($targetDir)
    {
        self::validateDirectory($targetDir);
        $targetDir = rtrim($targetDir, '/') . '/';

        return new self($targetDir);
    }

    /**
     * @param string $targetDir
     */
    private static function validateDirectory($targetDir)
    {
        if (!is_string($targetDir)) {
            throw new \RuntimeException('Directory must be string.');
        }
        if (!is_dir($targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%" does not exist.', $targetDir));
        }
    }

    /**
     * @param string $filePath
     */
    public function link($filePath)
    {
        $this->validateFile($filePath);
        symlink($filePath, $this->targetDir . basename($filePath));
    }

    /**
     * @param string $filePath
     */
    private function validateFile($filePath)
    {
        if (!is_string($filePath)) {
            throw new \RuntimeException('Link target must be string.');
        }
        if (!is_file($filePath)) {
            throw new \RuntimeException(sprintf('Link target "%s" does not exist.', $filePath));
        }
    }
}
