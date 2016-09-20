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
    /**
     * @param $customerId
     * @return bool
     */
    public function checkReturningCustomer($customerId)
    {
        $date = date("Y-m-d", Mage::getModel('core/date')->timestamp(time()));

        $dateInterval = Mage::getStoreConfig('intrum/customers/max_interval');
        $ordersNeeded = Mage::getStoreConfig('intrum/customers/orders_needed');
        $paymentMethodCode = explode(',', Mage::getStoreConfig('intrum/customers/payment_code'));

        $maxLastOrderDate = date("Y-m-d", strtotime($date . '-' . $dateInterval . ' days'));

        $id = md5(serialize([$customerId, $maxLastOrderDate]));

        $orders = unserialize(Mage::app()->getCache()->load($id));

        if (empty($orders)) {
            $resource = Mage::getSingleton('core/resource');
            $read = $resource->getConnection('default_setup');
            $select = $read
                ->select()
                ->from(array('order' => $resource->getTableName('sales/order')))
                ->join(array('order_payment' => $resource->getTableName('sales/order_payment')), 'order.entity_id = order_payment.parent_id', 'method')
                ->where('customer_id = ?', $customerId)
                ->where('status = ?', 'complete')
                ->order(array('DESC' => 'created_at'));

            $orders = $read->fetchAll($select);

            Mage::app()->getCache()->save(serialize($orders), $id, ['intrum'], 86400);
        }

        $validOrders = array();

        /* @var $order Mage_Sales_Model_Order */
        foreach ($orders as $order) {
            if (in_array($order['method'], $paymentMethodCode)) {
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