<?php

class Mondido_Payment_Model_Config extends Mage_Core_Model_Abstract
{
    protected $configPathPattern = 'payment/mondido_payments/%s';

    /**
     * Get merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        $configPath = sprintf($this->configPathPattern, 'merchant_id');

        return Mage::helper('core')->decrypt(Mage::getStoreConfig($configPath));
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        $configPath = sprintf($this->configPathPattern, 'api_password');

        return Mage::helper('core')->decrypt(Mage::getStoreConfig($configPath));
    }

    /**
     * Get secret
     *
     * @return string
     */
    public function getSecret()
    {
        $configPath = sprintf($this->configPathPattern, 'secret_key');

        return Mage::helper('core')->decrypt(Mage::getStoreConfig($configPath));
    }

    /**
     * Get payment action
     *
     * @return string
     */
    public function getPaymentAction()
    {
        $configPath = sprintf($this->configPathPattern, 'payment_action');

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive()
    {
        $configPath = sprintf($this->configPathPattern, 'active');

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Get order status
     *
     * @return string
     */
    public function getOrderStatus()
    {
        $configPath = sprintf($this->configPathPattern, 'order_status');

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Is test
     *
     * @return bool
     */
    public function isTest()
    {
        $configPath = sprintf($this->configPathPattern, 'test');

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Is debug enable
     *
     * @return bool
     */
    public function isDebugEnable()
    {
        $configPath = sprintf($this->configPathPattern, 'debug');

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Get default country
     *
     * @return string
     */
    public function getDefaultCountry()
    {
        $configPath = 'general/country/default';

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Get Magento edition
     *
     * @return string
     */
    public function getMagentoEdition()
    {
        return Mage::getEdition();
    }

    /**
     * Get module information.
     *
     * @return array
     */
    public function getModuleInformation()
    {
        $result = array();
        $configFile = Mage::getBaseDir().DS.'app'.DS.'etc'.DS.'modules'.DS.'Mondido_Payment.xml';
        $string = file_get_contents($configFile);
        $xml = simplexml_load_string($string);

        $result['name'] = 'Mondido_Payment';
        $result['setup_version'] = '0.0.1';
        $result['sequence'] = array(
            'Mage_Sales',
            'Mage_Payment',
            'Mage_Checkout'
        );

        return $result;
    }

    /**
     * Get allowed countries.
     *
     * @return string
     */
    public function getAllowedCountries()
    {
        $configPath = 'general/country/allow';

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Get is specific country.
     *
     * @return string
     */
    public function isAllowSpecific()
    {
        $configPath = sprintf($this->configPathPattern, 'allowspecific');

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Get specific countries.
     *
     * @return string
     */
    public function getSpecificCountries()
    {
        $configPath = sprintf($this->configPathPattern, 'specificcountry');

        return Mage::getStoreConfig($configPath);
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle()
    {
        $configPath = sprintf($this->configPathPattern, 'title');
        return Mage::getStoreConfig($configPath);
    }
}