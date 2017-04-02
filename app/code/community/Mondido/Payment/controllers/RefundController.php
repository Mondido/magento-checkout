<?php
/**
 * Class Mondido_Payment_RefundController
 */
class Mondido_Payment_RefundController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {   /** @var array $transaction */
        $transaction = $this->getRequest()->getPost();

        $this->logTransaction($transaction);
        /** @var  string $response */
        $response = $this->getHelper()->formatTransactionDataToJson($transaction);

        $this->getResponse()->setBody($response);
    }

    /**
     * Log transactions
     *
     * @param $transactions
     */
    private function logTransaction($transactions)
    {
        $this->getLogger()->logTransaction($transactions);
    }

    /**
     * Get Mondido Logger
     *
     * @return Mondido_Payment_Helper_Logger
     */
    private function getLogger()
    {
        return Mage::helper('mondido/logger');
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
}
