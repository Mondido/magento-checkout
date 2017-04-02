<?php

class Mondido_Payment_Helper_Url extends Mage_Checkout_Helper_Url
{
    /**
     * Retrieve checkout url
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        /** @var Mondido_Payment_Helper_Data $mondidHelper */
        $mondidHelper = Mage::helper('mondido');
        $url = $mondidHelper->getCheckoutUrl();

        if(!empty($url)){
            return $url;
        }

        return parent::getCheckoutUrl();
    }

}