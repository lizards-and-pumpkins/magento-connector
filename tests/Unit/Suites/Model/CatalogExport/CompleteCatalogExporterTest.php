<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api as LizardsAndPumpkinsApi;
use LizardsAndPumpkins_MagentoConnector_Helper_DataVersion as DataVersionHelper;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CompleteCatalogExporter as CompleteCatalogExporter;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator as ExportFilenameGenerator;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter as ExportFileWriter;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_CatalogExport_CatalogEntityIdCollector as CatalogEntityIdCollector;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CompleteCatalogExporter
 */
class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CompleteCatalogExporterTest
    extends \PHPUnit\Framework\TestCase
{
    /**
     * @return DataVersionHelper|PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockDataVersionHelper()
    {
        return $this->getMockBuilder(DataVersionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ExportFileWriter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function greateMockExportWriter()
    {
        return $this->getMockBuilder(ExportFileWriter::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return LizardsAndPumpkinsApi|PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockApi()
    {
        return $this->getMockBuilder(LizardsAndPumpkinsApi::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return CatalogEntityIdCollector|PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockEntityIdCollector()
    {
        return $this->getMockBuilder(CatalogEntityIdCollector::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ExportFilenameGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockExportFilenameGenerator()
    {
        return $this->getMockBuilder(ExportFilenameGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testExportsAllProducts()
    {
        $dummyProductIds = [42, 123];
        $testVersion = 'bar';
        $dummyFilename = 'foo.xml';

        $stubCatalogEntityIds = $this->createMockEntityIdCollector();
        $stubCatalogEntityIds->method('getAllProductIds')->willReturn($dummyProductIds);

        $stubExportFilenameGenerator = $this->createMockExportFilenameGenerator();
        $stubExportFilenameGenerator->method('getNewFilename')->willReturn($dummyFilename);

        $stubDataVersion = $this->createMockDataVersionHelper();
        $stubDataVersion->method('getTargetVersion')->willReturn($testVersion);
        
        $mockExportWriter = $this->greateMockExportWriter();
        $mockExportWriter->expects($this->once())->method('write')->with($dummyProductIds, [], $dummyFilename);

        $mockApi = $this->createMockApi();
        $mockApi->expects($this->once())->method('triggerCatalogImport')->with($dummyFilename, $testVersion);

        $exporter = new CompleteCatalogExporter(
            $mockExportWriter,
            $stubCatalogEntityIds,
            $stubExportFilenameGenerator,
            $stubDataVersion,
            $mockApi
        );
        $exporter->exportAllProducts();
    }

    public function testExportsAllCategories()
    {
        $dummyCategoryIds = [511, 612];
        $testVersion = 'baz';
        $dummyFilename = 'bar.xml';

        $stubCatalogEntityIds = $this->createMockEntityIdCollector();
        $stubCatalogEntityIds->method('getAllCategoryIds')->willReturn($dummyCategoryIds);

        $stubExportFilenameGenerator = $this->createMockExportFilenameGenerator();
        $stubExportFilenameGenerator->method('getNewFilename')->willReturn($dummyFilename);

        $stubDataVersion = $this->createMockDataVersionHelper();
        $stubDataVersion->method('getTargetVersion')->willReturn($testVersion);

        $mockExportWriter = $this->greateMockExportWriter();
        $mockExportWriter->expects($this->once())->method('write')->with([], $dummyCategoryIds, $dummyFilename);

        $mockApi = $this->createMockApi();
        $mockApi->expects($this->once())->method('triggerCatalogImport')->with($dummyFilename, $testVersion);

        $exporter = new CompleteCatalogExporter(
            $mockExportWriter,
            $stubCatalogEntityIds,
            $stubExportFilenameGenerator,
            $stubDataVersion,
            $mockApi
        );
        $exporter->exportAllCategories();
    }

    public function testExportsProductsForOneWebsite()
    {
        $dummyProductIds = [111, 222];
        $testVersion = 'xxx';
        $dummyFilename = 'foo.xml';
        $dummyWebsiteCode = 'bar';

        $stubCatalogEntityIds = $this->createMockEntityIdCollector();
        $stubCatalogEntityIds->method('getProductIdsForWebsite')->with($dummyWebsiteCode)->willReturn($dummyProductIds);

        $stubExportFilenameGenerator = $this->createMockExportFilenameGenerator();
        $stubExportFilenameGenerator->method('getNewFilename')->willReturn($dummyFilename);

        $stubDataVersion = $this->createMockDataVersionHelper();
        $stubDataVersion->method('getTargetVersion')->willReturn($testVersion);

        $mockExportWriter = $this->greateMockExportWriter();
        $mockExportWriter->expects($this->once())->method('write')->with($dummyProductIds, [], $dummyFilename);

        $mockApi = $this->createMockApi();
        $mockApi->expects($this->once())->method('triggerCatalogImport')->with($dummyFilename, $testVersion);

        $exporter = new CompleteCatalogExporter(
            $mockExportWriter,
            $stubCatalogEntityIds,
            $stubExportFilenameGenerator,
            $stubDataVersion,
            $mockApi
        );
        $exporter->exportProductsForWebsite($dummyWebsiteCode);
    }

    public function testExportsCategoriesForOneWebsite()
    {
        $dummyCategoryIds = [333, 444, 555];
        $testVersion = 'foo';
        $dummyFilename = 'bar.xml';
        $dummyWebsiteCode = 'baz';

        $stubCatalogEntityIds = $this->createMockEntityIdCollector();
        $stubCatalogEntityIds->method('getCategoryIdsForWebsite')
            ->with($dummyWebsiteCode)
            ->willReturn($dummyCategoryIds);

        $stubExportFilenameGenerator = $this->createMockExportFilenameGenerator();
        $stubExportFilenameGenerator->method('getNewFilename')->willReturn($dummyFilename);

        $stubDataVersion = $this->createMockDataVersionHelper();
        $stubDataVersion->method('getTargetVersion')->willReturn($testVersion);

        $mockExportWriter = $this->greateMockExportWriter();
        $mockExportWriter->expects($this->once())->method('write')->with([], $dummyCategoryIds, $dummyFilename);

        $mockApi = $this->createMockApi();
        $mockApi->expects($this->once())->method('triggerCatalogImport')->with($dummyFilename, $testVersion);

        $exporter = new CompleteCatalogExporter(
            $mockExportWriter,
            $stubCatalogEntityIds,
            $stubExportFilenameGenerator,
            $stubDataVersion,
            $mockApi
        );
        $exporter->exportCategoriesForWebsite($dummyWebsiteCode);
    }

}
