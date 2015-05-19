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
 * Class Diglin_Intrum_Adminhtml_LogController
 */
class Diglin_Intrum_Adminhtml_LogController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout()->_addContent($this->getLayout()->createBlock('diglin_intrum/admin_log'))->renderLayout();
    }

    public function editAction()
    {
        $this->loadLayout()->_addContent($this->getLayout()->createBlock('diglin_intrum/admin_log_edit'))->renderLayout();
    }
}
