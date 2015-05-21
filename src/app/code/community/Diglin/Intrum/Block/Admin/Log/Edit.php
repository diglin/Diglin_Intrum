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
 * Class Diglin_Intrum_Block_Admin_Log_Edit
 */
class Diglin_Intrum_Block_Admin_Log_Edit extends Mage_Core_Block_Abstract
{
    public function __construct()
    {
        $this->_headerText = Mage::helper('diglin_intrum')->__('Log view');
    }

    protected function _toHtml()
    {
        $logview = Mage::getModel('diglin_intrum/log')->load($this->getRequest()->getParam('id'));

        /* @var $logview Diglin_Intrum_Model_log */
        $domInput = new DOMDocument();
        $domInput->preserveWhiteSpace = false;
        $domInput->loadXML($logview->getData("request"));
        $elem = $domInput->getElementsByTagName('Request');
        $elem->item(0)->removeAttribute("UserID");
        $elem->item(0)->removeAttribute("Password");

        $domInput->formatOutput = true;
        libxml_use_internal_errors(true);
        $testXml = simplexml_load_string($logview->getData("response"));

        $domOutput = new DOMDocument();
        $domOutput->preserveWhiteSpace = false;

        if ($testXml) {
            $domOutput->loadXML($logview->getData("response"));
            $domOutput->formatOutput = true;
            echo '
            <a href="javascript:history.go(-1)">Back to log</a>
            <h1>Input & output XML</h1>
            <table width="50%">
                <tr>
                    <td>Input (Attributes Login & password removed)</td>
                    <td>Response</td>
                </tr>
                <tr>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">' . htmlspecialchars($domInput->saveXml()) . '</code></td>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">' . htmlspecialchars($domOutput->saveXml()) . '</code></td>
                </tr>
            </table>';
        } else {
            echo '
            <a href="javascript:history.go(-1)">Back to log</a>
            <h1>Input & output XML</h1>
            <table width="50%">
                <tr>
                    <td>Input (Attributes Login & password removed)</td>
                    <td>Response</td>
                </tr>
                <tr>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">' . htmlspecialchars($domInput->saveXml()) . '</code></td>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">Raw data: ' . $logview->getData("response") . '</code></td>
                </tr>
            </table>';
        }
    }

}