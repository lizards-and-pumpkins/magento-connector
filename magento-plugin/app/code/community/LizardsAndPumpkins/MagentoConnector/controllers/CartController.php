<?php

require_once 'Mage/Checkout/controllers/CartController.php';

class LizardsAndPumpkins_MagentoConnector_CartController extends Mage_Checkout_CartController
{
    public function addAction()
    {
        $session = $this->getSession();

        try {
            $sku = $this->getRequest()->getParam('sku');
            $qty = $this->getRequest()->getParam('qty');

            if (!is_numeric($qty)) {
                $qty = 1;
            }

            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product');
            $product->load($product->getIdBySku($sku));

            if (!$product->isVisibleInSiteVisibility()) {
                $this->addConfigurableProductForSimpleProduct($product, $qty);
            } else {
                $params = ['qty' => $qty];
                $this->addProductToCart($product, $params);
            }

            $session->setData('cart_was_updated', true);

            Mage::dispatchEvent('checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );
        } catch (Mage_Core_Exception $e) {
            $message = $e->getMessage();
            $session->addError($message);
        }

        if (!isset($product)) {
            $this->_redirect('/');
            return;
        }
        $this->redirect();
    }

    private function redirect()
    {
        if (!$this->getSession()->getNoCartRedirect(true)) {
            $this->goBack();
        }
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    private function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function goBack()
    {
        $returnUrl = $this->getRequest()->getParam('return_url');
        if ($returnUrl) {

            if (!$this->_isUrlInternal($returnUrl)) {
                throw new Mage_Exception('External urls redirect to "' . $returnUrl . '" denied!');
            }

            $this->getSession()->getMessages(true);
            $this->getResponse()->setRedirect($returnUrl);
        } elseif (!Mage::getStoreConfig('checkout/cart/redirect_to_cart')
            && !$this->getRequest()->getParam('in_cart')
            && $backUrl = $this->_getRefererUrl()
        ) {
            $this->getResponse()->setRedirect($backUrl);
        } else {
            if (($this->getRequest()->getActionName() === 'add') && !$this->getRequest()->getParam('in_cart')) {
                $this->getSession()->setContinueShoppingUrl($this->_getRefererUrl());
            }
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * @return Mage_Checkout_Model_Cart
     */
    private function getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     */
    private function addConfigurableProductForSimpleProduct(Mage_Catalog_Model_Product $product, $qty)
    {
        $configProductIds = Mage::getResourceSingleton('catalog/product_type_configurable')
            ->getParentIdsByChild($product->getId());
        /* @var Mage_Catalog_Model_Product $configProduct */
        $configProduct = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('visibility', $product->getVisibleInSiteVisibilities())
            ->addIdFilter($configProductIds)
            ->setOrder('entity_id', Varien_Data_Collection::SORT_ORDER_ASC)
            ->setPageSize(1)
            ->getFirstItem();

        if ($configProduct->isObjectNew()) {
            Mage::dispatchEvent('add_to_cart_failed', ['product' => $product]);
            Mage::throwException('Product was not found.');
        }

        /** @var $typeConfig Mage_Catalog_Model_Product_Type_Configurable */
        $typeConfig = $configProduct->getTypeInstance(true);
        $attributes = $typeConfig->getConfigurableAttributes($configProduct);

        $superAttributes = [];
        foreach ($attributes as $attributeId => $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $simpleProductAttributeValue = $product->getDataUsingMethod($productAttribute->getAttributeCode());
            $superAttributes[$productAttribute->getId()] = $simpleProductAttributeValue;
        }
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($configProduct);
        $configProduct->setStockItem($stockItem);
        $params = [
            'product' => $configProduct->getId(),
            'super_attribute' => $superAttributes,
            'qty' => $qty,
        ];

        $this->addProductToCart($configProduct, $params);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param mixed[] $params
     */
    private function addProductToCart(Mage_Catalog_Model_Product $product, array $params)
    {
        $cart = $this->getCart();
        $cart->addProduct($product, $params);
        $cart->save();

        if (! $cart->getQuote()->getData('has_error')) {
            $this->addSuccessMessage($product);
        }
    }

    private function addSuccessMessage(Mage_Catalog_Model_Product $product)
    {
        $productName = Mage::helper('core')->escapeHtml($product->getName());
        $this->getSession()->addSuccess($this->__('%s was added to your shopping cart.', $productName));
    }
}
