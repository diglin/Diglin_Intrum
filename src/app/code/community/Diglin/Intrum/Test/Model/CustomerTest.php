<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Drink_Sameday
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

class Diglin_Intrum_Test_Model_CustomerTest extends EcomDev_PHPUnit_Test_Case {

    /**
     * @var Diglin_Intrum_Model_Customer
     */
    protected $_customerIntrum;

    protected function setUp()
    {
        $this->_customerIntrum = new Diglin_Intrum_Model_Customer();
        parent::setUp();
    }

    public function testReturningCustomer()
    {
        $customerId = 1154;

        /* @var $result bool */
        $result = $this->_customerIntrum->checkReturningCustomer($customerId);
        $this->assertNotTrue($result, 'Returning Customer did not order enough to be skipped from Intrum Check');
    }
}
