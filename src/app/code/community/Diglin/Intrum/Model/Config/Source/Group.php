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
 * Class Diglin_Intrum_Model_Config_Source_Group
 */
class Diglin_Intrum_Model_Config_Source_Group
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('customer/group_collection')
                ->loadData()->toOptionArray();
        }
        return $this->_options;
    }
}
