<?php
namespace Brera\MagentoConnector\XmlBuilder;

class ProductContainer
{
    private $domDocument;

    public function __construct(\DomDocument $product)
    {
        $this->domDocument = $product;
    }

    /**
     * @return \DomDocument
     */
    public function getProductDomDocument()
    {
        return $this->domDocument;
    }


}
