<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins_MagentoConnector_Model_ExportQueue as ExportQueue;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter as ExportFileWriter;
use LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFilenameGenerator as ExportFilenameGenerator;
use LizardsAndPumpkins_MagentoConnector_Model_Resource_ExportQueue_Message_Collection as ExportQueueMessageCollection;

/**
 * @covers LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_Exporter
 */
class LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExporterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExportQueue|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockExportQueue;

    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockApi;

    /**
     * @var ExportFileWriter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFileWriter;

    /**
     * @var ExportFilenameGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stubExportFilenameGenerator;

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_Exporter
     */
    private function createExporter()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_Exporter(
            $this->mockExportQueue,
            $this->stubExportFilenameGenerator,
            $this->mockFileWriter,
            $this->mockApi
        );
    }

    protected function setUp()
    {
        $this->mockExportQueue = $this->createMock(ExportQueue::class);
        $this->mockApi = $this->createMock(Api::class);
        $this->mockFileWriter = $this->createMock(ExportFileWriter::class);
        $this->stubExportFilenameGenerator = $this->createMock(ExportFilenameGenerator::class);
    }

    public function testPopsProductMessages()
    {
        $this->mockExportQueue->expects($this->once())
            ->method('popQueuedProductUpdatesGroupedByDataVersion')
            ->willReturn([]);

        $this->createExporter()->exportQueuedProducts();
    }

    public function testPopsCategoryMessages()
    {
        $this->mockExportQueue->expects($this->once())
            ->method('popQueuedCategoryUpdatesGroupedByDataVersion')
            ->willReturn([]);

        $this->createExporter()->exportQueuedCategories();
    }

    public function testPopsAllCatalogUpdateMessages()
    {
        $this->mockExportQueue->expects($this->once())
            ->method('popQueuedUpdatesGroupedByDataVersion')
            ->willReturn([]);

        $this->createExporter()->exportQueuedProductsAndCategories();
    }

    public function testWritesTheCollectionWithTheGeneratedFilename()
    {
        $dummyDataVersion1 = 'foo';
        $dummyDataVersion2 = 'bar';

        $stubCollection1 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection1->method('getObjectIdsByType')->willReturnOnConsecutiveCalls([1, 2], [4, 5]);
        $stubCollection2 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection2->method('getObjectIdsByType')->willReturnOnConsecutiveCalls([10, 11], [13, 14]);

        $this->mockExportQueue->method('popQueuedProductUpdatesGroupedByDataVersion')
            ->willReturn([$dummyDataVersion1 => $stubCollection1, $dummyDataVersion2 => $stubCollection2]);

        $this->stubExportFilenameGenerator->method('getNewFilename')->willReturnOnConsecutiveCalls('a.xml', 'b.xml');

        $this->mockFileWriter->expects($this->exactly(2))->method('write')->withConsecutive(
            [[1, 2], [4, 5], 'a.xml'],
            [[10, 11], [13, 14], 'b.xml']
        );

        $this->createExporter()->exportQueuedProducts();
    }

    public function testTriggersTheCatalogImportApiWithTheGeneratedFilename()
    {
        $dummyDataVersion1 = 'foo';
        $dummyDataVersion2 = 'bar';

        $stubCollection1 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection1->method('getObjectIdsByType')->willReturn([]);
        $stubCollection2 = $this->createMock(ExportQueueMessageCollection::class);
        $stubCollection2->method('getObjectIdsByType')->willReturn([]);

        $this->mockExportQueue->method('popQueuedProductUpdatesGroupedByDataVersion')
            ->willReturn([$dummyDataVersion1 => $stubCollection1, $dummyDataVersion2 => $stubCollection2]);

        $this->stubExportFilenameGenerator->method('getNewFilename')->willReturnOnConsecutiveCalls('a.xml', 'b.xml');

        $this->mockApi->expects($this->exactly(2))->method('triggerCatalogImport')
            ->withConsecutive(
                ['a.xml', $dummyDataVersion1],
                ['b.xml', $dummyDataVersion2]
            );

        $this->createExporter()->exportQueuedProducts();
    }
}
