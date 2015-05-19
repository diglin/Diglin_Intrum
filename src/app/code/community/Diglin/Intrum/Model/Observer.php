<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    drink.ch
 * @package     drink.ch
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

use Diglin\Intrum\CreditDecision\Request;
use Diglin\Intrum\CreditDecision\Response;
use Diglin\Intrum\CreditDecision\Transport;

/**
 * Class Diglin_Intrum_Model_Observer
 */
class Diglin_Intrum_Model_Observer
{
    const CONFIG_PATH_PSR0NAMESPACES = 'global/psr0_namespaces';

    static $shouldAdd = true;

    /**
     * Get Namespaces To Register
     *
     * @return array
     */
    protected function _getNamespacesToRegister()
    {
        $namespaces = array();
        $node = Mage::getConfig()->getNode(self::CONFIG_PATH_PSR0NAMESPACES);
        if ($node && is_array($node->asArray())) {
            $namespaces = array_keys($node->asArray());
        }

        return $namespaces;
    }

    /**
     * Add PSR-0 Autoloader for our Diglin Intrum library
     *
     * Event
     * - resource_get_tablename
     * - add_spl_autoloader
     *
     * @return $this
     */
    public function addAutoloader()
    {
        if (!self::$shouldAdd) {
            return $this;
        }

        foreach ($this->_getNamespacesToRegister() as $namespace) {
            $namespace = str_replace('_', '/', $namespace);
            if (is_dir(Mage::getBaseDir('lib') . DS . $namespace)) {
                $args = array($namespace, Mage::getBaseDir('lib') . DS . $namespace);
                $autoloader = Mage::getModel("diglin_intrum/splAutoloader", $args);
                $autoloader->register();
            }
        }

        self::$shouldAdd = false;

        return $this;
    }

    /**
     * @return Diglin_Intrum_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('diglin_intrum');
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function checkAndCall(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('intrum/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }

        if (false === $this->isInCheckoutProcess()) {
            return;
        }

        $status = Mage::getSingleton('checkout/session')->getData('IntrumCDPStatus');
        $minimumAmount = Mage::getStoreConfig('intrum/api/minamount', Mage::app()->getStore());

        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();

        if (isset($status) && $quote->getGrandTotal() >= $minimumAmount) {
            $status = intval($status);
            $methods = $this->getHelper()->getAllowedAndDeniedMethods(Mage::getStoreConfig('intrum/risk/status' . $status, Mage::app()->getStore()));
            $method = $observer->getEvent()->getMethodInstance();
            $result = $observer->getEvent()->getResult();

            if (in_array($method->getCode(), $methods["denied"])) {
                $result->isAvailable = false;
            }
        }

        return;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function checkoutControllerOnepageSaveBillingMethod(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('intrum/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }

        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        if ($quote->isVirtual()) {
            $this->checkoutControllerOnepageSaveShippingMethod($observer);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function checkoutControllerOnepageSaveShippingMethod(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('intrum/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }

        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $request = $this->getHelper()->createShopRequest($quote);
        $xml = $request->saveXml();

        if ($intrumResponse = $this->sendRequest($xml, $request, $this->getHelper()->__('Intrum status'))) {
            $status = (int) $intrumResponse->getCustomerRequestStatus();
            if (intval($status) > 15) {
                $status = 0;
            }
            Mage::getSingleton('checkout/session')->setData('IntrumResponse', serialize($intrumResponse));
            Mage::getSingleton('checkout/session')->setData('IntrumCDPStatus', $status);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderPaymentPlaceEnd(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('intrum/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }

        $orderId = $observer->getData('order_ids');

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);
        $incrementId = $order->getIncrementId();

        if (empty($incrementId)) {
            return;
        }

        $paymentMethod = $order->getPayment()->getMethod();
        $request = $this->getHelper()->createShopRequestPaid($order, $paymentMethod);
        $xml = $request->saveXML();

        if ($this->sendRequest($xml, $request, $this->getHelper()->__('Order Paid'))) {
            $statusToPayment = Mage::getSingleton('checkout/session')->getData('IntrumCDPStatus');
            $IntrumResponseSession = Mage::getSingleton('checkout/session')->getData('IntrumResponse');

            if (!empty($statusToPayment) && !empty($IntrumResponseSession)) {
                $this->getHelper()->saveStatusToOrder($order, $statusToPayment, unserialize($IntrumResponseSession));
            }
        }
    }

    /**
     * @param $xml
     * @param Request $request
     * @param string $message
     * @return bool|Response
     */
    private function sendRequest($xml, Request $request, $message = '')
    {
        $timeOut = (int) Mage::getStoreConfig('intrum/api/timeout', Mage::app()->getStore());
        $mode = Mage::getStoreConfig('intrum/api/currentmode', Mage::app()->getStore());

        $intrumCommunicator = new Transport();
        if ($mode == 'production') {
            $intrumCommunicator->setMode('live');
        } else {
            $intrumCommunicator->setMode('test');
        }

        if ($xml instanceof DOMDocument) {
            $xml = $xml->saveXML();
        }

        $response = $intrumCommunicator->sendRequest($xml, $timeOut);
        if ($response) {
            $intrumResponse = new Response();
            $intrumResponse->setRawResponse($response);
            $intrumResponse->processResponse();
            $status = (int) $intrumResponse->getCustomerRequestStatus();
            if (intval($status) > 15) {
                $status = 0;
            }
            $this->getHelper()->saveLog($request, $xml, $response, $status, $message);
            return $intrumResponse;

        } else {
            $this->getHelper()->saveLog($request, $xml, $this->getHelper()->__('Empty response'), '0', $message);
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isInCheckoutProcess()
    {
        $places = Mage::getStoreConfig('intrum/advancedcall/activation', Mage::app()->getStore());
        $pl = explode("\n", $places);
        foreach ($pl as $place) {
            $segments = explode(',', trim($place));
            if (count($segments) == 2) {
                list($moduleName, $controllerName) = $segments;
                if (Mage::app()->getRequest()->getModuleName() == trim($moduleName) &&
                    Mage::app()->getRequest()->getControllerName() == trim($controllerName)
                ) {
                    return true;
                }
            }

        }

        return false;
    }
}
