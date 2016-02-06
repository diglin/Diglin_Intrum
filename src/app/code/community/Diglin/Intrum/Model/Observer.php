<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Intrum
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
     * Event
     * - payment_method_is_active
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkAndCall(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('intrum/api/pluginenabled', Mage::app()->getStore())) {
            return;
        }

        if (false === $this->isInCheckoutProcess()) {
            return;
        }

        $status = Mage::getSingleton('checkout/session')->getData('IntrumCDPStatus');
        $minimumAmount = Mage::getStoreConfig('intrum/api/minamount', Mage::app()->getStore());

        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();

        if (!$this->shouldbeChecked($quote)) {
            return;
        }

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
     * Event:
     * - controller_action_predispatch_checkout_onepage_saveBilling
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkoutControllerOnepageSaveBillingMethod(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('intrum/api/pluginenabled', Mage::app()->getStore())) {
            return;
        }

        if (false === $this->isInCheckoutProcess()) {
            return;
        }

        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();

        if (!$this->shouldbeChecked($quote)) {
            return;
        }

        $observer->setQuote($quote);

        if ($quote->isVirtual() || Mage::getStoreConfigFlag('intrum/advancedcall/real_onepagecheckout')) {
            $this->checkoutControllerOnepageSaveShippingMethod($observer);
        }
    }

    /**
     * Event:
     * - checkout_controller_onepage_save_shipping_method
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkoutControllerOnepageSaveShippingMethod(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('intrum/api/pluginenabled', Mage::app()->getStore())) {
            return;
        }

        $quote = $observer->getQuote();

        if (!$this->shouldbeChecked($quote)) {
            return;
        }

        $dom = $this->getHelper()->createShopRequest($quote);
        $xml = null;

        if ($dom) {
            $xml = $dom->saveXML();
        }

        if ($xml && $intrumResponse = $this->sendRequest($xml, $this->getHelper()->getRequest(), $this->getHelper()->__('Intrum status'))) {
            $status = (int)$intrumResponse->getCustomerRequestStatus();
            if (intval($status) > 15) {
                $status = 0;
            }
            Mage::getSingleton('checkout/session')->setData('IntrumResponse', serialize($intrumResponse));
            Mage::getSingleton('checkout/session')->setData('IntrumCDPStatus', $status);
        } else {
            Mage::log(Mage::helper('diglin_intrum')->__('Intrum status not set - DOM returned => %s', print_r($dom, true)));
        }
    }

    /**
     * Event
     * - checkout_onepage_controller_success_action
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderPaymentPlaceEnd(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('intrum/api/pluginenabled', Mage::app()->getStore())) {
            return;
        }

        $orderId = $observer->getData('order_ids');

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);
        $incrementId = $order->getIncrementId();

        if (empty($incrementId) || !$this->shouldbeChecked($order)) {
            return;
        }

        $paymentMethod = $order->getPayment()->getMethod();
        $dom = $this->getHelper()->createShopRequestPaid($order, $paymentMethod);
        $xml = null;

        if ($dom) {
            $xml = $dom->saveXML();
        }

        if ($xml && $this->sendRequest($xml, $this->getHelper()->getRequest(), $this->getHelper()->__('Order Closed'))) {
            $statusToPayment = Mage::getSingleton('checkout/session')->getData('IntrumCDPStatus');
            $intrumResponseSession = Mage::getSingleton('checkout/session')->getData('IntrumResponse');

            if (!empty($statusToPayment) && !empty($intrumResponseSession)) {
                $this->getHelper()->saveStatusToOrder($order, $statusToPayment, unserialize($intrumResponseSession));
            }
        } else {
            Mage::log(Mage::helper('diglin_intrum')->__('Order not closed - DOM returned => %s', print_r($dom, true)));
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
        $timeOut = (int)Mage::getStoreConfig('intrum/api/timeout', Mage::app()->getStore());
        $mode = Mage::getStoreConfig('intrum/api/currentmode', Mage::app()->getStore());

        $transport = new Transport();
        if ($mode == 'production') {
            $transport->setMode('live');
        } else {
            $transport->setMode('test');
        }

        if ($xml instanceof DOMDocument) {
            $xml = $xml->saveXML();
        }

        $response = $transport->sendRequest($xml, $timeOut);
        if ($response) {
            $intrumResponse = new Response();
            $intrumResponse->setRawResponse($response);
            $intrumResponse->processResponse();
            $status = (int)$intrumResponse->getCustomerRequestStatus();
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
    private function isInCheckoutProcess()
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

    /**
     * @param Mage_Sales_Model_Order | Mage_Sales_Model_Quote $object
     * @return bool
     */
    private function shouldbeChecked($object)
    {
        $shouldBeChecked = false;
        $customerGroups = explode(',', Mage::getStoreConfig('intrum/customers/groups'));
        $checkCompany = Mage::getStoreConfigFlag('intrum/customers/company');
        $company = $object->getBillingAddress()->getCompany();

        // Only customer belonging to a specific customer group(s) are checked
        if (in_array($object->getCustomerGroupId(), $customerGroups) || empty($customerGroups)) {
            $shouldBeChecked = true;

            // Companies are checked if option enabled
            if (!$checkCompany && !empty($company)) {
                $shouldBeChecked = false;
            }
        }

        if ($shouldBeChecked && $object->getCustomer() instanceof Mage_Customer_Model_Customer) {
            $customerId = $object->getCustomer()->getId();
            if (null != $customerId) {
                $shouldBeChecked = Mage::helper('diglin_intrum/customer')->checkReturningCustomer($customerId);
            }
        }

        return $shouldBeChecked;
    }
}
