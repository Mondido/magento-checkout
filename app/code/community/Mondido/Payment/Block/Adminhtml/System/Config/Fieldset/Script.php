<?php
/**
 * Class Mondido_Payment_Block_Adminhtml_System_Config_Fieldset_Script
 */
class Mondido_Payment_Block_Adminhtml_System_Config_Fieldset_Script
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @var string Template path
     */
    protected $_template = 'mondido/system/config/fieldset/script.phtml';

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
