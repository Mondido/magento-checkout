<?php

/**
 * Class Mondido_Payment_Block_Registration
 */
class Mondido_Payment_Block_Registration extends Mage_Checkout_Block_Onepage_Login
{
    /**
     * Override this method in descendants to produce html
     *
     * @return string
     */
    protected function _toHtml()
    {
        return '';
    }
}