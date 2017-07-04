<?php

namespace LizardsAndPumpkins\MagentoConnector\XmlBuilder;

class CatalogMerge
{
    /**
     * @var \XmlWriter
     */
    private $xml;

    private $mode = 'product';

    public function __construct()
    {
        $this->xml = new \XMLWriter();
        $this->xml->openMemory();
        $this->xml->setIndent(true);
        
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->startXml();
    }

    /**
     * @param XmlString $product
     * @return string
     */
    public function addProduct(XmlString $product)
    {
        if (!$this->isProductMode()) {
            throw new \RuntimeException(
                'Products can only be added during product mode, this means BEFORE any category is added.'
            );
        }
        $this->addXml($product);
        return $this->getPartialXmlString();
    }

    /**
     * @param XmlString $category
     * @return string
     */
    public function addCategory(XmlString $category)
    {
        if (!$this->isCategoryMode()) {
            $this->setCategoryMode();
        }
        $this->addXml($category);
        return $this->getPartialXmlString();
    }

    /**
     * @return string
     */
    public function finish()
    {
        $this->endXml();
        return $this->getPartialXmlString();
    }

    /**
     * @return string
     */
    private function getPartialXmlString()
    {
        return $this->xml->flush();
    }

    private function setCategoryMode()
    {
        $this->mode = 'category';
        $this->xml->endElement(); // end products
        $this->xml->startElement('listings');
    }

    /**
     * @return bool
     */
    private function isCategoryMode()
    {
        return 'category' === $this->mode;
    }

    /**
     * @return bool
     */
    private function isProductMode()
    {
        return 'product' === $this->mode;
    }

    private function startXml()
    {
        $this->xml->startElement('catalog');

        $attributes = [
            'xmlns'              => 'http://lizardsandpumpkins.com',
            'xmlns:xsi'          => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://lizardsandpumpkins.com ../../schema/catalog.xsd',
        ];

        foreach ($attributes as $attribute => $value) {
            $this->xml->writeAttribute($attribute, $value);
        }

        $this->xml->startElement('products');
    }

    private function endXml()
    {
        $this->xml->endElement();
        $this->xml->endElement();
    }

    /**
     * @param XmlString $catalogEntity
     */
    private function addXml(XmlString $catalogEntity)
    {
        $this->xml->writeRaw(PHP_EOL . $catalogEntity->getXml());
    }

}
