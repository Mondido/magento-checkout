<?php

class Mondido_Payment_Helper_Cart extends Mage_Checkout_Helper_Cart
{
    /**
     * Retrieve shopping cart url
     *
     * @return string
     */
    public function getCartUrl()
    {
        return parent::getCartUrl();
    }
}