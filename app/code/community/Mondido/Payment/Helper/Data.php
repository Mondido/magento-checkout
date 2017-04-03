<?php

/**
 * Class Mondido_Payment_Helper_Data
 */
class Mondido_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Format number
     *
     * Wrapper for number_format()
     *
     * @param float $number The number being formatted
     * @param int $decimals Sets the number of decimal points
     * @param string $decimalPoint Sets the separator for the decimal point
     * @param string $thousandsSeparator Sets the thousands separator
     * @return float
     */
    public function formatNumber($number, $decimals = 2, $decimalPoint = '.', $thousandsSeparator = '')
    {
        return number_format($number, $decimals, $decimalPoint, $thousandsSeparator);
    }

    /**
     * Format transaction to JSON
     *
     * @param $transaction
     * @return string
     * @throws Exception
     */
    public function formatTransactionDataToJson($transaction)
    {
        if (!isset($transaction) || empty($transaction)) {
            throw new Exception('Empty Transaction Data');
        }
        return Mage::helper('core')->jsonEncode($transaction);
    }

    /**
     * Format transaction from JSON
     *
     * @param $data
     * @param int $returnedParam
     * @return array
     */
    public function formatTransactionDataFromJson($data, $returnedParam = Zend_Json::TYPE_ARRAY)
    {
        return json_decode($data, $returnedParam);
    }

    /**
     * Get checkout url.
     *
     * @return null | string
     */
    public function getCheckoutUrl()
    {
        /** @var Mondido_Payment_Model_Config $config */
        $config = Mage::getModel('mondido/config');
        if($config->isActive()){
            return Mage::getUrl('mondido/checkout/index');
        }
        return null;
    }
}
