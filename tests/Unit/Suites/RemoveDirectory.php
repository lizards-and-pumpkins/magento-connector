<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector;

trait RemoveDirectory
{
    /**
     * @param string $dir
     */
    private function removeDirectoryRecursivly($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir((string)$file);
            } else {
                unlink((string)$file);
            }
        }
        rmdir($dir);
    }
}
