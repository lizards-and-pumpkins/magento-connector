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
            throw new \RuntimeException('Link target must be string.');
        }
        if (!is_file($filePath)) {
            throw new \RuntimeException(sprintf('Link target "%s" does not exist.', $filePath));
        }
    }

    /**
     * @param string $link
     * @return bool
     */
    private function linkExists($link)
    {
        return is_link($link);
    }

    /**
     * @param $filePath
     */
    private function validateLinkTarget($filePath)
    {
        if (is_file($this->targetDir . basename($filePath))) {
            throw new \RuntimeException('Link already exists as file.');
        }
    }
}
