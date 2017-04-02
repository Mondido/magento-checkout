<?php

/**
 * Checkout block
 *
 * @category Mondido
 * @package  Mondido_Payment
 *
 */
class Mondido_Payment_Block_Checkout extends Mage_Checkout_Block_Onepage
{
    /** @var Mondido_Payment_Helper_Data $_helper */
    protected $_helper;
    /** @var Mage_Checkout_Model_Session $_checkoutSession */
    protected $_checkoutSession;

    public function __construct(array $args)
    {
        $this->_helper = Mage::helper('mondido');
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        parent::__construct($args);
    }

    /**
     * Modifying - removing login step as in M2
     *
     * @return array
     */
    public function getSteps()
    {
        $steps = array();
        $stepCodes = $this->_getStepCodes();
        $stepCodes = array_diff($stepCodes, array('login'));

        foreach ($stepCodes as $step) {
            $steps[$step] = $this->getCheckout()->getStepData($step);
        }

        return $steps;
    }

    public function getMondidoTransactionUrl()
    {
        $transactionUrl = '';
        $quote = $this->_checkoutSession->getQuote();

        if(is_object($quote) && $quote->getId()>0){
            $transaction = json_decode($quote->getMondidoTransaction());
            if (property_exists($transaction, 'href')) {
                $transactionUrl = $transaction->href;
            }
        }

        return $transactionUrl;
    }
}