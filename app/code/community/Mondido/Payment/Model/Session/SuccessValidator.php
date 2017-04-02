<?php

/**
 * Success validator
 *
 * @category Mondido
 * @package  Mondido_Payment
 *
 */
class Mondido_Payment_Model_Session_SuccessValidator
{
    /** @var Mage_Checkout_Model_Session $_checkoutSession*/
    protected $_checkoutSession;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->_checkoutSession = Mage::getModel('checkout/session');
    }

    /**
     * Is valid
     *
     * @return bool
     */
    public function isValid()
    {
        if (!$this->_checkoutSession->getLastSuccessQuoteId()) {
            return false;
        }

        if (!$this->_checkoutSession->getLastQuoteId() || !$this->_checkoutSession->getLastRealOrderId()) {
            return false;
        }

        return true;
    }
}
