<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Intrum
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

/**
 * Class Diglin_Intrum_Block_Adminhtml_System_Config_Form_Fieldset_Customer_Groups
 */
class Diglin_Intrum_Block_Adminhtml_System_Config_Form_Fieldset_Customer_Groups extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected $_dummyElement;
    protected $_fieldRenderer;
    protected $_values;

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);

        $groups = Mage::getSingleton('payment/config')->getActiveMethods();
        foreach ($groups as $paymentCode => $paymentModel) {
            $html .= $this->_getFieldHtml($element, $paymentModel, $paymentCode);
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @return Varien_Object
     */
    protected function _getDummyElement()
    {
        if (empty($this->_dummyElement)) {
            $this->_dummyElement = new Varien_Object(array('show_in_default' => 1, 'show_in_website' => 1));
        }

        return $this->_dummyElement;
    }

    /**
     * @return object
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }

        return $this->_fieldRenderer;
    }

    /**
     * @return array
     */
    protected function _getValues()
    {
        if (empty($this->_values)) {
            $this->_values = array(
                array('label' => Mage::helper('diglin_intrum')->__('INVOICE'), 'value' => 'INVOICE'),
                array('label' => Mage::helper('diglin_intrum')->__('DIRECT-DEBIT'), 'value' => 'DIRECT-DEBIT'),
                array('label' => Mage::helper('diglin_intrum')->__('CREDIT-CARD'), 'value' => 'CREDIT-CARD'),
                array('label' => Mage::helper('diglin_intrum')->__('PRE-PAY'), 'value' => 'PRE-PAY'),
                array('label' => Mage::helper('diglin_intrum')->__('CASH-ON-DELIVERY'), 'value' => 'CASH-ON-DELIVERY'),
                array('label' => Mage::helper('diglin_intrum')->__('E-PAYMENT'), 'value' => 'E-PAYMENT'),
                array('label' => Mage::helper('diglin_intrum')->__('PAYMENT'), 'value' => 'PAYMENT')
            );
        }

        return $this->_values;
    }

    /**
     * @param $fieldset
     * @param $group
     * @param $paymentCode
     * @return mixed
     */
    protected function _getFieldHtml($fieldset, $group, $paymentCode)
    {
        $configData = $this->getConfigData();
        $path = 'intrum/mappings/group_' . $paymentCode;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        } else {
            $data = 'INVOICE';
            $inherit = true;
        }

        $element = $this->_getDummyElement();

        $field = $fieldset->addField($group->getId(), 'select',
            array(
                'name'                  => 'groups[mappings][fields][group_' . $paymentCode . '][value]',
                'label'                 => $paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title'),
                'value'                 => $data,
                'values'                => $this->_getValues(),
                'inherit'               => $inherit,
                'can_use_default_value' => $this->getForm()->canUseDefaultValue($element),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue($element),
            ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }
}