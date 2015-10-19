<?php

class Brera_MagentoConnector_CartController extends Mage_Core_Controller_Front_Action
{
    public function addAction()
    {
        $sku = $this->getRequest()->getParam('sku');
        $qty = $this->getRequest()->getParam('qty');

        if (!is_numeric($qty)) {
            $qty = 1;
        }

        $product = Mage::getModel('catalog/product');
        $product->load($product->getIdBySku($sku));

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->addProduct($product, $qty);

        $this->_getCart()->save();
        $this->_getSession()->setCartWasUpdated(true);

        Mage::dispatchEvent('checkout_cart_add_product_complete',
            array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
        );

        $this->redirect($product);
    }

    private function redirect(Mage_Catalog_Model_Product $product)
    {
        $cart = $this->_getCart();
        if (!$this->_getSession()->getNoCartRedirect(true)) {
            if (!$cart->getQuote()->getHasError()) {
                $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                $this->_getSession()->addSuccess($message);
            }
            $this->_goBack();
        }
    }

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

    private function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }
}
