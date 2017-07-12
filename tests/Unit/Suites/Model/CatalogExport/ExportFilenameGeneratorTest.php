<?php

use LizardsAndPumpkins_MagentoConnector_Model_MagentoConfig as MagentoConfig;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator as ExportFilenameGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator
 */
class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGeneratorTest extends TestCase
{
    public function testCombinesExportPathAndFilename()
    {
        /** @var MagentoConfig|\PHPUnit_Framework_MockObject_MockObject $stubConfig */
        $stubConfig = $this->createMock(MagentoConfig::class);
        $stubConfig->method('getLocalPathForProductExport')->willReturn('foo/');
        $stubConfig->method('getLocalFilename')->willReturn('bar.xml');
        
        $filenameGenerator = new ExportFilenameGenerator($stubConfig);
        $this->assertSame('foo/bar.xml', $filenameGenerator->getNewFilename());
    }

    public function testAddsSeparatorAfterPathIfMissig()
    {
        /** @var MagentoConfig|\PHPUnit_Framework_MockObject_MockObject $stubConfig */
        $stubConfig = $this->createMock(MagentoConfig::class);
        $stubConfig->method('getLocalPathForProductExport')->willReturn('foo');
        $stubConfig->method('getLocalFilename')->willReturn('bar.xml');

        $filenameGenerator = new ExportFilenameGenerator($stubConfig);
        $this->assertSame('foo/bar.xml', $filenameGenerator->getNewFilename());
    }
}
