<?php

require_once Mage::getModuleDir('controllers', 'Mage_Checkout').DS.'OnepageController.php';
class Mondido_Payment_CheckoutController extends Mage_Checkout_OnepageController
{
    /**
     * Checkout page
     */
    public function indexAction()
    {
        Mage::dispatchEvent('controller_action_predispatch_mondido_checkout_index',
            array('controller_action' => $this)
        );
        /** @var Mondido_Payment_Model_Config $configModel */
        $configModel = Mage::getModel('mondido/config');
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');

        if (!$configModel->isActive()) {
            $checkoutSession->addError($this->__('Mondido checkout is turned off.'));
            $this->_redirect('checkout/cart');
            return;
        }

        /** @var Mage_Checkout_Helper_Data $checkoutHelper */
        $checkoutHelper = Mage::helper('checkout');
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getOnepage()->getQuote();
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }

        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                $checkoutHelper->__('Subtotal must exceed minimum order amount');

            $checkoutSession->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }

        if (!$customerSession->isLoggedIn() && !$checkoutHelper->isAllowedGuestCheckout($quote)) {
            $checkoutSession->addError($this->__('Guest checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }

        $customerSession->renewSession();
        $checkoutSession->setCartWasUpdated(false);
        $customerSession->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));
        $this->getOnepage()->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->renderLayout();
    }

    /**
     * Order success action
     */
    public function successAction()
    {
        $session = $this->getOnepage()->getCheckout();
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        $lastRecurringProfiles = $session->getLastRecurringProfileIds();
        if (!$lastQuoteId) {
            $this->_redirect('checkout/cart');
            return;
        }

        $session->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
        $this->renderLayout();
    }

    /**
     * Failure action
     */
    public function failureAction()
    {
        $lastQuoteId = $this->getOnepage()->getCheckout()->getLastQuoteId();
        $lastOrderId = $this->getOnepage()->getCheckout()->getLastOrderId();

        if (!$lastQuoteId || !$lastOrderId) {
            $this->_redirect('checkout/cart');
            return;
        }
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');

        $message = $this->getRequest()->getParam('error_name');
        $checkoutSession->addError($this->__($message));

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Redirect after payment confirmed.
     *
     * @return void
     */
    public function redirectAction()
    {
        $session = $this->getOnepage()->getCheckout();

        $quote = $this->getOnepage()->getQuote();
        $quote->reserveOrderId()->setIsActive(false)->save();

        $reservedOrderId = $quote->getReservedOrderId();
        $quoteId = $quote->getId();

        $session->setLastQuoteId($quoteId)
            ->setLastSuccessQuoteId($quoteId)
            ->clearHelperData();

        $session->setLastRealOrderId($reservedOrderId);
        $this->loadLayout();
        $this->renderLayout();
        return;
    }

    /**
     * Error action.
     *
     */
    public function errorAction()
    {
        $message = $this->getRequest()->getParam('error_name');
        /**@var Mage_Customer_Model_Session $session*/
        $session = Mage::getSingleton('customer/session');
        $session->addError($this->__($message));
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($message));
        return;
    }
}
