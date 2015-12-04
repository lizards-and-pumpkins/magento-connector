<?php

namespace Brera\MagentoConnector\XmlBuilder;

require_once('XmlString.php');

class CatalogMerge
{
    /**
     * @var \XmlWriter
     */
    private $xml;

    private $started = false;

    private $mode = 'product';

    public function __construct()
    {
        $this->xml = new \XMLWriter();
        $this->xml->openMemory();
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->startXml();
    }

    /**
     * @param XmlString $product
     */
    public function addProduct(XmlString $product)
    {
        if (!$this->isProductMode()) {
            throw new \RuntimeException(
                'Products can only be added during product mode, this means BEFORE any category is added.'
            );
        }
        $this->addXml($product);
    }

    /**
     * @param XmlString $category
     */
    public function addCategory(XmlString $category)
    {
        if (!$this->isCategoryMode()) {
            $this->setCategoryMode();
        }
        $this->addXml($category);
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
    public function getPartialXmlString()
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
    public function isCategoryMode()
    {
        return $this->mode === 'category';
    }

    /**
     * @return bool
     */
    public function isProductMode()
    {
        return $this->mode === 'product';
    }

    private function startXml()
    {
        if ($this->started) {
            return;
        }
        $this->started = true;
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
     * @param XmlString $category
     */
    private function addXml(XmlString $category)
    {
        $this->xml->writeRaw($category->getXml());
    }

}
