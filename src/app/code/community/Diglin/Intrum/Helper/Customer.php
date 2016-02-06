<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Matias Orlando <support at diglin.com>
 * @category    Diglin_Intrum
 * @package     Diglin_Intrum
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

/**
 * Class Diglin_Intrum_Helper_Customer
 */
class Diglin_Intrum_Helper_Customer extends Mage_Core_Model_Abstract
{
    public function checkReturningCustomer($customerId)
    {
        $date = date("Y-m-d", Mage::getModel('core/date')->timestamp(time()));

        $dateInterval = Mage::getStoreConfig('intrum/customers/max_interval');
        $ordersNeeded = Mage::getStoreConfig('intrum/customers/orders_needed');
        $paymentMethodCode = explode(',', Mage::getStoreConfig('intrum/customers/payment_code'));
        $maxLastOrderDate = date("Y-m-d", strtotime($date . '-' . $dateInterval . ' days'));

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', 'complete')
            ->addAttributeToSort('created_at', 'DESC');

        $validOrders = array();

        /* @var $order Mage_Sales_Model_Order */
        foreach ($orders as $order) {
            $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
            if (in_array($paymentMethod, $paymentMethodCode)) {
                $validOrders[] = $order;
            }
        }

        if (count($validOrders) >= $ordersNeeded) {
            foreach ($validOrders as $validOrder) {
                if ($validOrder->getCreatedAt() > $maxLastOrderDate) {
                    return false;
                }
            }
        }

        return true;
    }
}