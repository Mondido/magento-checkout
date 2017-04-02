<?php

class Mondido_Payment_Model_Adminhtml_Source_PaymentAction
{
    /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '',
                'label' => Mage::helper('mondido')->__('-- Please Select --')
            ),
            array(
                'label' => Mage::helper('mondido')->__('Authorize and Capture'),
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE
            ),
            array(
                'label' => Mage::helper('mondido')->__('Authorize'),
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE
            ),
        );
    }
}