<?php

namespace LizardsAndPumpkins\MagentoConnector\Images;

class ImageLinker
{
    /**
     * @var string
     */
    private $targetDir;

    /**
     * @param string $targetDir
     */
    private function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * @param string $targetDir
     * @return ImageLinker
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
        if ($this->linkExists($this->targetDir . basename($filePath))) {
            return;
        }
        $this->validateLinkTarget($filePath);
        symlink($filePath, $this->targetDir . basename($filePath));
    }

    /**
     * @param string $filePath
     */
    private function validateFile($filePath)
    {
        if (!is_string($filePath)) {
            throw new \InvalidArgumentException('Link target must be string.');
        }
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf('Link target "%s" does not exist.', $filePath));
        }
    }

    /**
     * @param string $filePath
     * @return bool
     */
    private function linkExists($filePath)
    {
        return is_link($filePath);
    }

    /**
     * @param string $filePath
     */
    private function validateLinkTarget($filePath)
    {
        if (is_file($this->targetDir . basename($filePath))) {
            throw new \RuntimeException('Link already exists as file.');
        }
    }
}
