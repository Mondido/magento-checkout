<?php

class Mondido_Payment_PaymentController extends Mage_Core_Controller_Front_Action
{
    const HTTP_INTERNAL_ERROR = 500;

    const HTTP_BAD_REQUEST = 400;

    /**@var Mondido_Payment_Model_Api2_Transaction $_transaction*/
    protected $_transaction;

    /**@var Mondido_Payment_Helper_Data $_helper*/
    protected $_helper;

    /**@var Mondido_Payment_Helper_Iso $_isoHelper*/
    protected $_isoHelper;

    /**@var Mondido_Payment_Helper_Logger $_logger*/
    protected $_logger;

    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs
    )
    {
        $this->_helper = Mage::helper('mondido');
        $this->_logger = Mage::helper('mondido/logger');
        $this->_isoHelper = Mage::helper('mondido/iso');
        $this->_transaction = Mage::getModel('mondido/api2_transaction');
        parent::__construct($request, $response, $invokeArgs);
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Iframe page which submits the payment data to Moneybookers.
     */
    public function formAction()
    {
    }

    /**
     * Payment index action.
     *
     */
    public function indexAction()
    {
        $data = $this->getRequest()->getPost();
        $this->_logger->logMessages(var_export($data, true));

        $result = array();
        $resultJson = $this->getResponse();
        if (array_key_exists('status', $data) && in_array($data['status'], ['authorized'])) {
            $session = $this->_getCheckout();
            $quoteId = $data['payment_ref'];
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());

            if ($session->getLastRealOrderId()) {
                $orderIsAlreadyCreated = true;
            } else {
                $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore()->getId());
                $quote->load($quoteId);
                //$quote = $session->getQuote();

                if ($quote->getIsActive()) {
                    $order = false;
                    $result['error'] = 'Quote is still active in Magento, please try again in a while.';
                    $resultJson->setHttpResponseCode(self::HTTP_INTERNAL_ERROR);
                } else if ($data['amount'] !== $this->_helper->formatNumber($quote->getBaseGrandTotal())) {
                    $order = false;
                    $result['error'] = 'Wrong amount'.$data['amount'].' -- '.$this->_helper->formatNumber($quote->getBaseGrandTotal());
                    $resultJson->setHttpResponseCode(self::HTTP_BAD_REQUEST);
                } else {
                    try {
                        $transactionJson = $this->_transaction->show($data['id']);
                        $transaction = json_decode($transactionJson);

                        $shippingAddress = $quote->getShippingAddress();
                        $shippingAddress->setFirstname($transaction->payment_details->first_name);
                        $shippingAddress->setLastname($transaction->payment_details->last_name);
                        $shippingAddress->setStreet(
                            array(
                            $transaction->payment_details->address_1,
                            $transaction->payment_details->address_2
                            )
                        );
                        $shippingAddress->setCity($transaction->payment_details->city);
                        $shippingAddress->setPostcode($transaction->payment_details->zip);
                        $shippingAddress->setTelephone($transaction->payment_details->phone ?: '0');
                        $shippingAddress->setEmail($transaction->payment_details->email);
                        $shippingAddress->setCountryId($this->_isoHelper->convertFromAlpha3($transaction->payment_details->country_code));
                        $shippingAddress->save();

                        $billingAddress = $quote->getBillingAddress();
                        $billingAddress->setFirstname($transaction->payment_details->first_name);
                        $billingAddress->setLastname($transaction->payment_details->last_name);
                        $billingAddress->setStreet(
                            array(
                                $transaction->payment_details->address_1,
                                $transaction->payment_details->address_2
                            )
                        );
                        $billingAddress->setCity($transaction->payment_details->city);
                        $billingAddress->setPostcode($transaction->payment_details->zip);
                        $billingAddress->setTelephone($transaction->payment_details->phone ?: '0');
                        $billingAddress->setEmail($transaction->payment_details->email);
                        $billingAddress->setCountryId(
                            $this->_isoHelper->convertFromAlpha3($transaction->payment_details->country_code)
                        );
                        $billingAddress->save();

                        $quote->getPayment()->importData(array('method' => 'mondido_payments'));
                        $quote->getPayment()->setAdditionalInformation('id', $data['id']);
                        $quote->getPayment()->setAdditionalInformation('href', $data['href']);
                        $quote->getPayment()->setAdditionalInformation('status', $data['status']);

                        $quote->collectTotals()->save();
                        $quote->setCheckoutMethod('guest');

                        if ($quote->getData('checkout_method') === 'guest') {
                            $quote->setCustomerId(null);
                            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
                            $quote->setCustomerIsGuest(true);
                            $quote->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
                        }

                        $quote->save();
                        /** @var $service Mage_Sales_Model_Service_Quote */
                        $service = Mage::getModel('sales/service_quote', $quote);
                        $service->submitOrder();
                    } catch (Exception $e) {
                        $order = false;
                        $this->_logger->logMessages($e->getMessage());
                        $result['error'] = $e->getMessage();
                        $resultJson->setHttpResponseCode(self::HTTP_BAD_REQUEST);
                    }
                }
            }

            if ($order) {
                $result['order_ref'] = $order->getIncrementId();

                if (isset($orderIsAlreadyCreated) && $orderIsAlreadyCreated) {
                    $this->_logger->logMessages('Order was already created for quote ID ' . $quoteId);
                } else {
                    $this->_logger->logMessages('Order created for quote ID ' . $quoteId);
                    if ($order->getCanSendNewEmailFlag()) {
                        try {
                            $order->queueNewOrderEmail();
                            $order->setCanSendNewEmailFlag(false)->save();
                        } catch (Exception $e) {
                            $this->_logger->logMessages($e->getMessage().' '. $quoteId);
                        }
                    }
                }
            } else {
                $this->_logger->logMessages('Order could not be created for quote ID ' . $quoteId);
            }
        }

        if (array_key_exists('status', $data) && in_array($data['status'], ['approved'])) {
            $quoteId = $data['payment_ref'];
            /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);

            /** @var Mage_Sales_Model_Order $order */
            $order = $orderCollection->getFirstItem();
            if(is_object($order) && $order->getId()>0){
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING,
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    Mage::helper('mondido')->__('Payment is captured.')
                );
                try{
                    $order->save();
                }catch (Exception $e) {
                    $this->_logger->logMessages($e->getMessage().' OrderId - '. $order->getId());
                }

            }
        }

        $response = json_encode($result);
        $resultJson->setBody($response);
    }

    /**
     * Action to which the customer will be returned when the payment is made.
     */
    public function successAction()
    {
    }

    /**
     * Error Action.
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
