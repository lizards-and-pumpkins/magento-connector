<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;
use LizardsAndPumpkins\MagentoConnector\Api\HttpApiClient;
use LizardsAndPumpkins\MagentoConnector\Api\InsecurePhpStreamHttpApiClient;
use LizardsAndPumpkins\MagentoConnector\Api\PhpStreamHttpApiClient;
use LizardsAndPumpkins\MagentoConnector\Images\ImageLinker;
use LizardsAndPumpkins\MagentoConnector\Images\ImagesCollector;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\CatalogMerge;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\ListingXml;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\ProductBuilder;
use LizardsAndPumpkins\MagentoConnector\XmlBuilder\StockBuilder;

class LizardsAndPumpkins_MagentoConnector_Helper_Factory
{
    /**
     * @var callable
     */
    private $imageExporterFactory;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    private $config;

    private static $autoloaderRegistered = false;

    public function __construct()
    {
        $this->setImageExportStrategySymlink();

        $this->registerLibraryAutoloader();
    }

    private function registerLibraryAutoloader()
    {
        if (self::$autoloaderRegistered) {
            return;
        }
        self::$autoloaderRegistered = true;
        spl_autoload_register(function ($class) {
            $prefix = 'LizardsAndPumpkins\\';
            if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                return;
            }
            $classFile = str_replace('\\', '/', $class) . '.php';
            $file = BP . '/lib/' . $classFile;
            if (file_exists($file)) {
                require $file;
            }
        }, false, true);
    }

    /**
     * @return CatalogMerge
     */
    public function createCatalogMerge()
    {
        return new CatalogMerge();
    }

    /**
     * @return ListingXml
     */
    public function createListingXml()
    {
        return new ListingXml($this->getConfig());
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_XmlUploader
     */
    public function createXmlUploader($xmlFilename)
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_XmlUploader($xmlFilename);
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder
     */
    public function createPrepareProductDataForXmlBuilder()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder($this);
    }

    /**
     * @param int[] $productIds
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductDataCollector
     */
    public function createProductDataCollector(array $productIds)
    {
        $stores = $this->getStoresToExport();
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductDataCollector($productIds, $stores);
    }

    /**
     * @param int[] $categoryIds
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector
     */
    public function createCategoryCollector(array $categoryIds)
    {
        $collector = new LizardsAndPumpkins_MagentoConnector_Model_Export_CategoryCollector(
            $categoryIds,
            $this->getConfig()->getCategoryUrlSuffix()
        );

        if ($stores = $this->getStoresToExport()) {
            $collector->setStoresToExport($stores);
        }

        return $collector;
    }

    /**
     * @return Mage_Core_Model_Store[]
     */
    private function getStoresToExport()
    {
        if ($config = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/stores_to_export')) {
            return array_map(
                function ($storeId) {
                    return Mage::app()->getStore($storeId);
                },
                array_filter(explode(',', $config))
            );
        }

        return Mage::app()->getStores();
    }

    /**
     * @return ImagesCollector
     */
    public function createImageCollector()
    {
        return new ImagesCollector();
    }

    /**
     * @return \LizardsAndPumpkins\MagentoConnector\Images\ImageExporter
     */
    public function createImageExporter()
    {
        $targetDir = $this->getConfig()->getImageTargetDirectory();
        if ($this->validateDirectory($targetDir)) {
            $targetDir = $this->getConfig()->getLocalPathForProductExport() . '/product-images';
        }
        $factory = $this->getImageExporterFactory();

        return $factory($targetDir);
    }

    public function setImageExporterFactory(callable $imageExporterFactory)
    {
        $this->imageExporterFactory = $imageExporterFactory;
    }

    public function disableImageExport()
    {
        $this->setImageExporterFactory(function () {
            return new \LizardsAndPumpkins\MagentoConnector\Images\NullImageExporter();
        });
    }

    public function setImageExportStrategySymlink()
    {
        $this->setImageExporterFactory(function ($targetDir) {
            return new ImageLinker($targetDir);
        });
    }

    /**
     * @return \Closure
     */
    private function getImageExporterFactory()
    {
        return $this->imageExporterFactory;
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig
     */
    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = new LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig();
        }

        return $this->config;
    }

    /**
     * @param mixed[] $productData
     * @param string[] $context
     * @return ProductBuilder
     */
    public function createProductBuilder(array $productData, array $context)
    {
        return new ProductBuilder($productData, $context);
    }

    /**
     * @param string $targetDir
     * @return bool
     */
    private function validateDirectory($targetDir)
    {
        return ! is_string($targetDir) || ! is_dir($targetDir);
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Catalog_CategoryUrlKeyService
     */
    public function createCategoryUrlKeyService()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Catalog_CategoryUrlKeyService($this->getCategoryCollection());
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Resource_Catalog_Category_Collection
     */
    private function getCategoryCollection()
    {
        return Mage::getResourceSingleton('lizardsAndPumpkins_magentoconnector/catalog_category_collection');
    }

    /**
     * @return Api
     */
    public function createLizardsAndPumpkinsApi()
    {
        return new Api(
            Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url'),
            $this->createHttpApiClient()
        );
    }

    /**
     * @return StockBuilder
     */
    public function createStockBuilder()
    {
        return new StockBuilder();
    }

    /**
     * @return HttpApiClient
     */
    public function createHttpApiClient()
    {
        return Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/disable_tls_peer_verification') ?
            new InsecurePhpStreamHttpApiClient() :
            new PhpStreamHttpApiClient();
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_ExportQueue
     */
    public function createExportQueue()
    {
        return Mage::getModel('lizardsAndPumpkins_magentoconnector/exportQueue');
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter
     */
    public function createExportFileWriter()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_ExportFileWriter(
            $this,
            $this->createCatalogDataforStoresCollector()
        );
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollector
     */
    public function createCatalogDataforStoresCollector()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_CatalogDataForStoresCollector(
            $this->getStoresToExport());
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory
     */
    public function createCategoryDataCollectionFactory()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_CategoryDataCollectionFactory(
            $this->getConfig()->getCategoryUrlSuffix()
        );
    }

    public function createProductDataCollectionFactory()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_CatalogExport_DataCollector_ProductDataCollectionFactory();
    }
}
