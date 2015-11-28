<?php

class Brera_MagentoConnector_CartController extends Mage_Core_Controller_Front_Action
{
    public function addAction()
    {
        try {
            $sku = $this->getRequest()->getParam('sku');
            $qty = $this->getRequest()->getParam('qty');

            if (!is_numeric($qty)) {
                $qty = 1;
            }

            $product = Mage::getModel('catalog/product');
            $product->load($product->getIdBySku($sku));

            $quote = Mage::getSingleton('checkout/session')->getQuote();

            if (!$product->isVisibleInSiteVisibility()) {
                $this->addConfigurableProductForSimpleProduct($product, $qty);
            } else {
                $quote->addProduct($product, $qty);
            }

            $this->_getCart()->save();
            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
        } catch (Mage_Core_Exception $e) {
            $message = $e->getMessage();
            $this->_getSession()->addError($message);
        }


        $this->redirect($product);
    }

    private function redirect(Mage_Catalog_Model_Product $product)
    {
        $cart = $this->_getCart();
        if (!$this->_getSession()->getNoCartRedirect(true)) {
            if (!$cart->getQuote()->getHasError()) {
                $message = $this->__('%s was added to your shopping cart.',
                    Mage::helper('core')->escapeHtml($product->getName()));
                $this->_getSession()->addSuccess($message);
            }
            $this->_goBack();
        }
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    private function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function _goBack()
    {
        $returnUrl = $this->getRequest()->getParam('return_url');
        if ($returnUrl) {

            if (!$this->_isUrlInternal($returnUrl)) {
                throw new Mage_Exception('External urls redirect to "' . $returnUrl . '" denied!');
            }

            $this->_getSession()->getMessages(true);
            $this->getResponse()->setRedirect($returnUrl);
        } elseif (!Mage::getStoreConfig('checkout/cart/redirect_to_cart')
            && !$this->getRequest()->getParam('in_cart')
            && $backUrl = $this->_getRefererUrl()
        ) {
            $this->getResponse()->setRedirect($backUrl);
        } else {
            if (($this->getRequest()->getActionName() == 'add') && !$this->getRequest()->getParam('in_cart')) {
                $this->_getSession()->setContinueShoppingUrl($this->_getRefererUrl());
            }
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * @return Mage_Checkout_Model_Cart
     */
    private function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param int                        $qty
     */
    private function addConfigurableProductForSimpleProduct(Mage_Catalog_Model_Product $product, $qty)
    {
        $configProductIds = Mage::getResourceSingleton('catalog/product_type_configurable')
            ->getParentIdsByChild($product->getId());
        /* @var Mage_Catalog_Model_Product $configProduct */
        $configProduct = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('visibility', $product->getVisibleInSiteVisibilities())
            ->addIdFilter($configProductIds)
            ->setOrder('entity_id', Varien_Data_Collection::SORT_ORDER_ASC)
            ->setPageSize(1)
            ->getFirstItem();

        if ($configProduct->isObjectNew()) {
            Mage::throwException('Product was not found.');
        }

        /** @var $typeConfig Mage_Catalog_Model_Product_Type_Configurable */
        $typeConfig = $configProduct->getTypeInstance(true);
        $attributes = $typeConfig->getConfigurableAttributes($configProduct);

        $superAttributes = array();
        foreach ($attributes as $attributeId => $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $simpleProductAttributeValue = $product->getDataUsingMethod($productAttribute->getAttributeCode());
            $superAttributes[$productAttribute->getId()] = $simpleProductAttributeValue;
        }
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configProduct);
        $configProduct->setStockItem($stockItem);
        $params = array(
            'product'         => $configProduct->getId(),
            'super_attribute' => $superAttributes,
            'qty'             => $qty,
        );

        $this->_getCart()->addProduct($configProduct, $params);
    }
}
