<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin_Intrum
 * @package     Diglin_Intrum
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

/**
 * Class Diglin_Intrum_Model_Config_Source_Modes
 */
class Diglin_Intrum_Model_Config_Source_Modes extends Mage_Core_Model_Config_Data
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('diglin_intrum');

        $methods = array(
            array("label" => $helper->__("Test"),       "value" => "test"),
            array("label" => $helper->__("Production"), "value" => "production")
        );

        return $methods;
    }
}