<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;
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
     * @var CatalogMerge
     */
    private $catalogMerge;

    /**
     * @var LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader
     */
    private $productXmlUploader;

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
    
    public function reset()
    {
        $this->catalogMerge = null;
        $this->productXmlUploader = null;
    }

    /**
     * @return CatalogMerge
     */
    public function getCatalogMerge()
    {
        if (null === $this->catalogMerge) {
            $this->catalogMerge = new CatalogMerge();
        }
        return $this->catalogMerge;
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
     * @return LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader
     */
    public function getProductXmlUploader()
    {
        if (null === $this->productXmlUploader) {
            $this->productXmlUploader = new LizardsAndPumpkins_MagentoConnector_Model_ProductXmlUploader();
        }
        return $this->productXmlUploader;
    }

    /**
     * @return string
     */
    public function getProductXmlFilename()
    {
        return $this->getProductXmlUploader()->getFilename();
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder
     */
    public function createPrepareProductDataForXmlBuilder()
    {
        return new LizardsAndPumpkins_MagentoConnector_Model_Export_PrepareProductDataForXmlBuilder(
            $this->getCatalogMerge(),
            $this->getProductXmlUploader()
        );
    }

    /**
     * @return LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector
     */
    public function createProductCollector()
    {
        $helper = Mage::helper('lizardsAndPumpkins_magentoconnector/export');
        $collector = new LizardsAndPumpkins_MagentoConnector_Model_Export_ProductCollector($helper);

        if ($config = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/stores_to_export')) {
            $stores = array_map(
                function ($storeId) {
                    return Mage::app()->getStore($storeId);
                },
                array_filter(explode(',', $config))
            );
            $collector->setStoresToExport($stores);
        }
        return $collector;
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
        return !is_string($targetDir) || !is_dir($targetDir);
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
            new PhpStreamHttpApiClient()
        );
    }

    /**
     * @return StockBuilder
     */
    public function createStockBuilder()
    {
        return new StockBuilder();
    }
}
