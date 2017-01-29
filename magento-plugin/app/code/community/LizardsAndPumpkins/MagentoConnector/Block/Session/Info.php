<?php
declare(strict_types=1);

class LizardsAndPumpkins_MagentoConnector_Block_Session_Info extends Mage_Checkout_Block_Cart_Sidebar
{
    /**
     * @return string
     */
    public function getFormattedCartTotal()
    {
        $quote = $this->getQuote();
        $grandTotal = $quote->getGrandTotal();

        return $quote->getStore()->formatPrice($grandTotal, false);
    }

    /**
     * @return boolean
     */
    public function isCustomerLoggedIn()
    {
        return !empty($this->getCustomer()->getId());
    }
}
