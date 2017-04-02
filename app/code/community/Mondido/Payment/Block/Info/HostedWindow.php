<?php

class Mondido_Payment_Block_Info_HostedWindow extends Mage_Payment_Block_Info
{
    /** @var  Mondido_Payment_Model_Api2_Transaction */
    private $transaction;

    /** @var  Mondido_Payment_Helper_Data */
    private $mondidoHelper;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mondido/info/hostedwindow.phtml');
        $this->transaction = $this->getTransationApiModel();
        $this->mondidoHelper = $this->getHelperMondido();
    }

    /**
     * Prepare information specific to current payment method
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null === $this->_paymentSpecificInformation) {
            if (null === $transport) {
                $transport = new Varien_Object;
            } elseif (is_array($transport)) {
                $transport = new Varien_Object($transport);
            }
            $info = $this->getInfo();
            $transaction = $this->transaction->show($info->getAdditionalInformation('id'));
            /** @var array Varien_Object $data */
            $data = $this->mondidoHelper->formatTransactionDataFromJson($transaction, Zend_Json::TYPE_ARRAY);

            $transport->setData('ID', $data['id']);
            $transport->setData('Reference', $data['payment_ref']);
            $transport->setData('Status', $data['status']);
            $transport->setData('Payment method', $data['transaction_type']);
            $transport->setData('Card type', $data['payment_details']['card_type']);
            $transport->setData('Card number', $data['payment_details']['card_number']);
            $transport->setData('Card holder', $data['payment_details']['card_holder']);
            $transport->setData('SSN', $data['payment_details']['ssn']);
            $transport->setData('Currency', strtoupper($data['currency']));
            $transport->setData('Payment link', $data['href']);
            $transport->setData('Created at', $data['created_at']);
            $transport->setData('Processed at', $data['processed_at']);
            /** @var  $transport */
            $transport = parent::_prepareSpecificInformation($transport);

            Mage::dispatchEvent('payment_info_block_prepare_specific_information', array(
                'transport' => $transport,
                'payment' => $this->getInfo(),
                'block' => $this,
            ));
            $this->_paymentSpecificInformation = $transport;
        }
        return $this->_paymentSpecificInformation;
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
     * @return Mage_Core_Block_Abstract
     */
    public function getHelperMondido()
    {
        return Mage::helper('mondido');
    }
}
