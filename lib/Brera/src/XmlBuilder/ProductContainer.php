<?php
namespace Brera\MagentoConnector\Xml\Product;

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
