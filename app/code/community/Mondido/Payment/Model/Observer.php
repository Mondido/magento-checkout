<?php

class Mondido_Payment_Model_Observer extends Mage_Core_Model_Abstract
{
    /** @var Mondido_Payment_Model_Config $_config */
    protected $_config;
    /** @var Mondido_Payment_Model_Api2_Transaction $_transaction */
    protected $_transaction;
    /** @var Mondido_Payment_Helper_Data $_helper */
    protected $_helper;

    /**
     * Mondido_Payment_Model_Observer constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_config = Mage::getModel('mondido/config');
        $this->_transaction = Mage::getModel('mondido/api2_transaction');
        $this->_helper = Mage::helper('mondido');
    }

    public function mondidoCheckoutPredispatch(Varien_Event_Observer $observer)
    {
        /**@var Mage_Sales_Model_Quote $quote*/
        $quote = $observer->getEvent()->getControllerAction()->getOnepage()->getQuote();

        $customer = $quote->getCustomer();

        if($this->_config->isAllowSpecific()) {
            $allowedCountries = explode(',', $this->_config->getSpecificCountries());
        }else{
            $allowedCountries = explode(',', $this->_config->getAllowedCountries());
        }

        $defaultCountry = $this->_config->getDefaultCountry();

        $forceDefaultCountry = true;

        if (is_object($customer) && $customer->getId()) {
            $addressId = $customer->getDefaultShipping();
            $address = $customer->getAddressById($addressId);

            $quote->getShippingAddress()->importCustomerAddress($address);
            $quote->getBillingAddress()->importCustomerAddress($address);

            if (in_array($address->getCountryId(), $allowedCountries)) {
                $forceDefaultCountry = false;
            }
        }

        $shippingAddress = $quote->getShippingAddress();

        if ($forceDefaultCountry == true) {
            if (!$shippingAddress->getCountryId()) {
                $shippingAddress->setCountryId($defaultCountry)->save();
            }
        }

        if (!$shippingAddress->getShippingMethod()) {
            $shippingAddress->setShippingMethod('flatrate_flatrate')
                ->setCollectShippingRates(true)
                ->collectShippingRates()
                ->save();

            $quote->collectTotals()->save();
        }

        if ($quote->getId()) {
            if (!$quote->getMondidoTransaction()) {
                $response = $this->_transaction->create($quote);
            } else {
                $response = $this->_transaction->update($quote);
            }

            if ($response) {
                $data = json_decode($response);

                if (property_exists($data, 'id')) {

                    $quote->setMondidoTransaction($response);
                    $quote->save();
                } else {
                    $message = sprintf(
                        $this->_helper->__("Mondido returned error code %d: %s (%s)"),
                        $data->code,
                        $data->description,
                        $data->name
                    );

                    $url = Mage::getUrl('checkout/cart');

                    /** @var Mage_Checkout_Model_Session $checkoutSession */
                    $checkoutSession = Mage::getSingleton('checkout/session');
                    $checkoutSession->addError($this->_helper->__($message));

                    $observer->getControllerAction()
                        ->getResponse()
                        ->setRedirect($url);
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function observeAfterCustomerSave(Varien_Event_Observer $observer)
    {
        /** @var Mondido_Payment_Model_Api2_Customer $customerApi */
        $customerApi = Mage::getModel('mondido/api2_customer');
        $customerObject = $observer->getCustomer();
        $customerApi->handle($customerObject);

        return $observer;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function observeSubmitBeforeQuote(Varien_Event_Observer $observer)
    {
        $mondidoTransaction = $observer->getQuote()->getMondidoTransaction();
        $observer->getOrder()->setMondidoTransaction($mondidoTransaction);
        return $observer;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Mondido_Payment_Model_Observer
     */
    public function replaceCheckoutLink(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();

        if($block instanceof Mage_Checkout_Block_Onepage_Link){
            $block->setTemplate('mondido/checkout/link.phtml');
        }
        return $this;
    }
}

