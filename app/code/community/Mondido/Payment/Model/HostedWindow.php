<?php

/**
 * Class Mondido_Payment_Model_HosterWindow
 */
class Mondido_Payment_Model_HostedWindow extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'mondido_payments';

    /**
     * @var string
     */
    protected $_formBlockType = 'payment/form_checkmo';

    /**
     * @var string
     */
    protected $_infoBlockType = 'mondido/info_hostedWindow';

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Mondido API wrapper for transaction
     *
     * @var $transaction Mondido_Payment_Model_Api2_Transaction
     */
    private $transaction;

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return bool
     * @throws Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $this->transaction = $this->getTransationApiModel();

        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        /** @var  array|stdClass $result */
        $result = $this->transaction->capture($order, $amount);
        $result = $this->getHelper()->formatTransactionDataFromJson($result, Zend_Json::TYPE_OBJECT);

        if (property_exists($result, 'code') && $result->code != 200) {
            $message = sprintf(
                "Mondido returned error code %d: %s (%s)",
                $result->code,
                $result->description,
                $result->name
            );
            throw new Exception($message);
        }

        $payment->setTransactionId($result->id)->setIsTransactionClosed(false);
        $payment->setAdditionalInformation('id', $result->id);
        $payment->setAdditionalInformation('href', $result->href);
        $payment->setAdditionalInformation('status', $result->status);

        return true;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $captureTxnId = $payment->getParentTransactionId();

        if (!$captureTxnId) {
            throw new Exception(
                'We can\'t issue a refund transaction because there is no capture transaction.'
            );
        }

        $this->transaction = $this->getTransationApiModel();

        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        $canRefundMore = $payment->getCreditmemo()->getInvoice()->canRefund();
        $isFullRefund = !$canRefundMore &&
            0 == (double)$order->getBaseTotalOnlineRefunded() + (double)$order->getBaseTotalOfflineRefunded();

        $result = $this->transaction->refund($order, $amount);
        $result = $this->getHelper()->formatTransactionDataFromJson($result, Zend_Json::TYPE_OBJECT);

        if (property_exists($result, 'code') && $result->code != 200) {
            $message = sprintf(
                "Mondido returned error code %d: %s (%s)",
                $result->code,
                $result->description,
                $result->name
            );

            throw new Exception($message);
        }

        $payment->setTransactionId($result->id)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(!$canRefundMore);

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @return $this
     */
    public function void(Varien_Object $payment)
    {
        return $this;
    }

    /**
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        return false;
    }

    /**
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        return false;
    }

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }

        $path = 'payment/mondido_payments/' . $field;

        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Return Api Transaction Model
     *
     * @return Mondido_Payment_Model_Api2_Transaction
     */
    private function getTransationApiModel()
    {
        return Mage::getModel('mondido/api2_transaction');
    }

    /**
     * Get Mondido Helper
     *
     * @return Mondido_Payment_Helper_Data
     */
    private function getHelper()
    {
        return Mage::helper('mondido');
    }

    /**
     * Return url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('mondido/payment/redirect', array('_secure' => true));
    }

}
