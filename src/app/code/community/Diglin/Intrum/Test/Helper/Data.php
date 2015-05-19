<?php
/**
 * Diglin GmbH - Switzerland
 *
 * This file is part of a Diglin_Intrum module.
 *
 * This Diglin GmbH module is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Intrum
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 * @license     http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

/**
 * Class Diglin_Intrum_Test_Helper_Data
 */
class Diglin_Intrum_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    protected function setUp()
    {
        $this->_subject = Mage::helper('diglin_intrum');
        return parent::setUp();
    }

    /**
     * @test
     */
    public function testXmlRequest()
    {
        $this->assertTrue(false, 'Bad guy');
    }
}