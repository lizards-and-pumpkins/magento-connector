<?php
declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Images;

class ImageLinker implements ImageExporter
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
        self::validateDirectory($targetDir);
        $targetDir = rtrim($targetDir, '/') . '/';
        $this->targetDir = $targetDir;
    }

    /**
     * @param string $targetDir
     */
    private static function validateDirectory($targetDir)
    {
        if (!is_string($targetDir)) {
            throw new \InvalidArgumentException('Directory must be string.');
        }
        if (!is_dir($targetDir)) {
            throw new \InvalidArgumentException(sprintf('Directory "%s" does not exist.', $targetDir));
        }
    }

    /**
     * @param string $filePath
     */
    public function export($filePath)
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
