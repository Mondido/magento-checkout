<?php

class Mondido_Payment_Model_Api2_Customer extends Mondido_Payment_Model_Api2_Mondido
{
    public $resource = 'customers';
    /** @var Mondido_Payment_Model_Config $_config */
    protected $_config;
    /** @var Mage_Checkout_Model_Session $_checkoutSession */
    protected $_checkoutSession;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->_adapter = new Varien_Http_Adapter_Curl();
        $this->_config = Mage::getModel('mondido/config');
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
    }

    /**
     * Handle a Magento customer.
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool
     */
    public function handle(Mage_Customer_Model_Customer $customer)
    {
        if(!$this->_config->isActive()){
            return false;
        }

        if (is_object($customer) && $customer->getId()) {
            $mondidoId = $this->getIdByRef($customer->getId());

            if ($mondidoId) {
                return $this->update($mondidoId, $customer);
            } else {
                return $this->create($customer);
            }
        }

        return false;
    }

    /**
     * Create new customer at Mondido.
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool
     */
    public function create(Mage_Customer_Model_Customer $customer)
    {
        $metaData = $this->buildMetadata($customer);

        $jsonResponse = $this->call(
            'POST',
            $this->resource,
            array(),
            array(
                'ref' => $customer->getId(),
                'metadata' => json_encode($metaData)
            )
        );

        if (!$jsonResponse) {
            return false;
        }

        $response = json_decode($jsonResponse);
        if (is_array($response)) {
            $response = current($response);
            if ($response && is_object($response) && property_exists($response, 'id')) {
                if ($response->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Update existing customer at Mondido.
     *
     * @param integer|string $mondidoId
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool
     */
    public function update($mondidoId, Mage_Customer_Model_Customer $customer)
    {
        $metaData = $this->buildMetadata($customer);

        $jsonResponse = $this->call(
            'PUT',
            $this->resource,
            array($mondidoId),
            array(
                'metadata' => json_encode($metaData)
            )
        );

        if (!$jsonResponse) {
            return false;
        }

        $response = json_decode($jsonResponse);
        if (is_array($response)) {
            $response = current($response);
            if ($response && is_object($response) && property_exists($response, 'id')) {
                if ($response->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Fetch Mondido ID using "ref" field (Magento customer ID).
     *
     * @param integer $referenceId Magento customer ID
     *
     * @return bool
     */
    public function getIdByRef($referenceId)
    {
        $jsonResponse = $this->call(
            'GET',
            $this->resource,
            array(),
            array('filter[ref]' => $referenceId)
        );

        if (!$jsonResponse) {
            return false;
        }

        $response = json_decode($jsonResponse);
        if (is_array($response)) {
            $response = current($response);
            if ($response && is_object($response) && property_exists($response, 'id')) {
                return $response->id;
            }
        }

        return false;
    }

    /**
     * Build meta data for Mondido.
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return array
     */
    public function buildMetadata(Mage_Customer_Model_Customer $customer)
    {
        $metaData = array(
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail(),
        );

        $addressFields = array(
            'firstname',
            'middlename',
            'lastname',
            'company',
            'street',
            'postcode',
            'city',
            'country_id',
            'region',
            'telephone',
            'vat_id'
        );

        if ($customer->getDefaultBilling()) {
            $billingAddress = $customer->getAddressById($customer->getDefaultBilling());
            if (is_object($billingAddress) && $billingAddress->getId()) {
                foreach ($addressFields as $fieldKey) {
                    $metaData['billing_' . $fieldKey] = $billingAddress->getData($fieldKey);
                }
            }
        }

        if ($customer->getDefaultShipping()) {
            $shippingAddress = $customer->getAddressById($customer->getDefaultShipping());
            if ($shippingAddress && $shippingAddress->getId()) {
                foreach ($addressFields as $fieldKey) {
                    $metaData['shipping_' . $fieldKey] = $shippingAddress->getData($fieldKey);
                }
            }
        }

        return $metaData;
    }
}