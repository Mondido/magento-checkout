<?php

class Mondido_Payment_Model_Api2_Transaction extends Mondido_Payment_Model_Api2_Mondido
{
    public $resource = 'transactions';
    /** @var Mondido_Payment_Model_Config $_config */
    protected $_config;
    protected $_storeManager;
    protected $urlBuilder;
    /** @var Mondido_Payment_Helper_Data $helper */
    protected $_helper;
    /** @var Mage_Checkout_Model_Session $_checkoutSession */
    protected $_checkoutSession;
    /** @var Mondido_Payment_Helper_Iso $isoHelper */
    protected $_isoHelper;


    public function __construct()
    {
        $this->_adapter = new Varien_Http_Adapter_Curl();
        $this->_config = Mage::getModel('mondido/config');
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        $this->_helper = Mage::helper('mondido');
        $this->_isoHelper = Mage::helper('mondido/iso');
    }

    /**
     * Create hash.
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return string
     */
    protected function _createHash(Mage_Sales_Model_Quote $quote)
    {
        $hashRecipe = array(
            'merchant_id' => $this->_config->getMerchantId(),
            'payment_ref' => $quote->getId(),
            'customer_ref' => $quote->getCustomerId() ? $quote->getCustomerId() : '',
            'amount' => $this->_helper->formatNumber($quote->getBaseGrandTotal()),
            'currency' => strtolower($quote->getBaseCurrencyCode()),
            'test' => $this->_config->isTest() ? 'test' : '',
            'secret' => $this->_config->getSecret()
        );

        $hash = md5(implode($hashRecipe));

        return $hash;
    }

    /**
     * Create transaction.
     *
     * @param int|Mage_Sales_Model_Quote $quote A quote object or ID
     *
     * @return string
     */
    public function create($quote)
    {
        if (!is_object($quote)) {
            $quote = Mage::getModel('sales/quote')->load($quote);
        }

        $method = 'POST';

        $webhook = array(
            'url' => Mage::getUrl('mondido/payment/index'),
            'trigger' => 'payment',
            'http_method' => 'post',
            'data_format' => 'form_data'
        );

        $metaData = $this->getMetaData($quote);
        $transactionItems = $this->getItems($quote);
        $shippingAddress = $quote->getShippingAddress();

        $data = array(
            'merchant_id' => $this->_config->getMerchantId(),
            'amount' => $this->_helper->formatNumber($quote->getBaseGrandTotal()),
            'vat_amount' => $this->_helper->formatNumber($shippingAddress->getBaseTaxAmount()),
            'payment_ref' => $quote->getId(),
            'test' => $this->_config->isTest() ? 'true' : 'false',
            'metadata' => $metaData,
            'currency' => strtolower($quote->getBaseCurrencyCode()),
            'hash' => $this->_createHash($quote),
            'process' => 'false',
            'success_url' => Mage::getUrl('mondido/checkout/redirect'),
            'error_url' => Mage::getUrl('mondido/checkout/error'),
            'authorize' => 'true',
            'items' => json_encode($transactionItems),
            'webhook' => json_encode($webhook),
            'payment_details' => $metaData['user']
        );

        if ($quote->getCustomerId()) {
            $data['customer_ref'] = $quote->getCustomerId();
        }

        return $this->call($method, $this->resource, null, $data);
    }

    /**
     * Capture transaction.
     *
     * @param Mage_Sales_Model_Order $order
     * @param float                      $amount Amount to capture
     *
     * @return string
     */
    public function capture(Mage_Sales_Model_Order $order, $amount)
    {
        $method = 'PUT';

        $transaction = json_decode($order->getMondidoTransaction());

        if (property_exists($transaction, 'id')) {
            $id = $transaction->id;
        } else {
            return false;
        }

        // Assure remote transaction only is reserved
        $currentTransactionJson = $this->show($id);
        $currentTransaction = json_decode($currentTransactionJson);

        if (is_object($currentTransaction) && property_exists($currentTransaction, 'status')) {
            if ($currentTransaction->status == 'authorized') {
                $data = ['amount' => $this->_helper->formatNumber($amount)];

                return $this->call($method, $this->resource, [$id, 'capture'], $data);
            } else {
                return $currentTransactionJson;
            }
        }

        return true;
    }

     /**
     * Update transaction
     *
     * @param int|Mage_Sales_Model_Quote $quote A quote object or ID
     *
     * @return string|boolean
     */
    public function update($quote)
    {

        if (!is_object($quote)) {
            $quote = Mage::getModel('sales/quote')->load($quote);
        }

        $transaction = $this->_helper->formatTransactionDataFromJson(
            $quote->getMondidoTransaction(),
            Zend_Json::TYPE_OBJECT);

        if (property_exists($transaction, 'id')) {
            $id = $transaction->id;
        } else {
            return false;
        }

        $method = 'PUT';

        $metaData = $this->getMetaData($quote);
        $transactionItems = $this->getItems($quote);
        $shippingAddress = $quote->getShippingAddress();

        $data = array(
            'amount' => $this->_helper->formatNumber($quote->getBaseGrandTotal()),
            'vat_amount' => $this->_helper->formatNumber($shippingAddress->getBaseTaxAmount()),
            'metadata' => $metaData,
            'currency' => strtolower($quote->getBaseCurrencyCode()),
            'hash' => $this->_createHash($quote),
            'items' => json_encode($transactionItems),
            'process' => 'false'
        );

        if ($quote->getCustomerId()) {
            $data['customer_ref'] = $quote->getCustomerId();
        }

        return $this->call($method, $this->resource, (string) $id, $data);
    }

    /**
     * Capture transaction
     *
     * @param Mage_Sales_Model_Order  $order
     * @param float                      $amount Amount to capture
     *
     * @return string
     */
    public function refund(Mage_Sales_Model_Order $order, $amount)
    {
        $method = 'POST';

        $transaction = json_decode($order->getMondidoTransaction());

        if (property_exists($transaction, 'id')) {
            $id = $transaction->id;
        } else {
            return false;
        }

        $data = array(
            'amount' => $this->_helper->formatNumber($amount),
            'reason' => 'Refund from Magento',
            'transaction_id' => $id
        );

        return $this->call($method, 'refunds', null, $data);
    }

    /**
     * Get quote meta data
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function getMetaData(Mage_Sales_Model_Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        /**@var Mage_Customer_Model_Address $shippingMethods */
        $shippingMethods = $shippingAddress->getGroupedAllShippingRates();

        $shippingData = array();



        if (is_array($shippingMethods)) {
            foreach ($shippingMethods as $group) {
                foreach ($group as $code => $rate) {
                    /**@var Mage_Sales_Model_Quote_Address_Rate $rate*/
                    if ($rate->getCode() ) {
                        $shippingData[] = array(
                            'carrier_code' => $rate->getCarrier(),
                            'method_code' => $rate->getCode(),
                            'carrier_title' => $rate->getCarrierTitle(),
                            'method_title' => $rate->getMethodTitle(),
                            'amount' => $rate->getPrice(),
                            'base_amount' => $rate->getPrice(),
                            'available' => 1,
                            'error_message' => $rate->getErrorMessage(),
                            'price_excl_tax' => $rate->getPrice(),
                            'getPriceInclTax' => $rate->getPrice()
                        );
                    }
                }
            }
        }

        $paymentDetails = array(
            'email' => $shippingAddress->getEmail(),
            'phone' => $shippingAddress->getTelephone(),
            'first_name' => $shippingAddress->getFirstname(),
            'last_name' => $shippingAddress->getLastname(),
            'zip' => $shippingAddress->getPostcode(),
            'address_1' => $shippingAddress->getStreet(1),
            'address_2' => $shippingAddress->getStreet(2),
            'city' => $shippingAddress->getCity(),
            'country_code' => $this->_isoHelper->transform($shippingAddress->getCountryId())
        );

        if($this->_config->isAllowSpecific()) {
            $allowedCountries = $this->_config->getSpecificCountries();
        }else{
            $allowedCountries = $this->_config->getAllowedCountries();
        }

        $defaultCountry = $this->_config->getDefaultCountry();

        $data = array(
            'user' => $paymentDetails,
            'products' => $this->getItems($quote),
            'magento' => array(
                'edition' => $this->_config->getMagentoEdition(),
                'version' => $this->_config->getMagentoVersion(),
                'php' => phpversion(),
                'module' => $this->_config->getModuleInformation(),
                'configuration' => array(
                    'general' => array(
                        'country' => array(
                            'allow' => $allowedCountries,
                            'default' => $defaultCountry
                        )
                    )
                ),
                'shipping_methods' => $shippingData
            )
        );

        return $data;
    }

    /**
     * Get quote items data
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function getItems(Mage_Sales_Model_Quote $quote)
    {
        $quoteItems = $quote->getAllVisibleItems();

        $transactionItems = array();

        foreach ($quoteItems as $item) {
            /**@var Mage_Sales_Model_Quote_Item $item*/
            $transactionItems[] = array(
                'artno' => $item->getSku(),
                'description' => $item->getName(),
                'qty' => $item->getQty(),
                'amount' => $this->_helper->formatNumber($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()),
                'vat' => $this->_helper->formatNumber($item->getTaxPercent()),
                'discount' => $this->_helper->formatNumber($item->getBaseDiscountAmount())
            );
        }

        if (!$quote->isVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $baseShippingAmount = $shippingAddress->getBaseShippingAmount();

            if ($baseShippingAmount > 0) {
                $shippingVat = $baseShippingAmount / ($baseShippingAmount - $shippingAddress->getBaseShippingTaxAmount());
            } else {
                $shippingVat = 0;
            }

            $transactionItems[] = array(
                'artno' => $shippingAddress->getShippingMethod(),
                'description' => $shippingAddress->getShippingDescription(),
                'qty' => 1,
                'amount' => $this->_helper->formatNumber($baseShippingAmount),
                'vat' => $this->_helper->formatNumber($shippingVat),
                'discount' => $this->_helper->formatNumber($shippingAddress->getBaseShippingDiscountAmount())
            );
        }

        return $transactionItems;
    }

    /**
     * Show transaction
     *
     * @param int $id Transaction ID
     *
     * @return string
     */
    public function show($id)
    {
        $method = 'GET';

        return $this->call($method, $this->resource, (string) $id);
    }
}