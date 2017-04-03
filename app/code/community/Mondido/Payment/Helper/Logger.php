<?php
/**
 * Class Mondido_Payment_Helper_Iso
 */
class Mondido_Payment_Helper_Logger extends Mage_Core_Model_Logger
{
    const MONDIDO_LOG = 'mondido_transaction.log';

    const MONDIDO_LOG_ADDITIONAL = 'debug_payment_mondido.log';

    /**
     * Log transactions
     *
     * @param string $transaction
     */
    public function logTransaction($transaction)
    {
        if($this->isDebugModeEnabled()) {
            $this->log($transaction, null, self::MONDIDO_LOG, true);
        }
    }

    /**
     * Log.
     *
     * @param string $message
     */
    public function logMessages($message)
    {
        if($this->isDebugModeEnabled()) {
            $this->log($message, null, self::MONDIDO_LOG_ADDITIONAL, true);
        }
    }

    protected function isDebugModeEnabled()
    {
        /** @var Mondido_Payment_Model_Config $config */
        $config = Mage::getModel('mondido/config');

        return (boolean) $config->isDebugEnable();
    }
}
