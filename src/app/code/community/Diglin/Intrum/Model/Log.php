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
 * Class Diglin_Intrum_Model_Log
 *
 * @method int getLogId()
 * @method string getFirstname()
 * @method string getLastname()
 * @method string getTown()
 * @method string getStreet()
 * @method string getCountry()
 * @method string getPostcode()
 * @method string getIp()
 * @method string getStatus()
 * @method string getRequestId()
 * @method string getType()
 * @method string getError()
 * @method string getResponse()
 * @method string getRequest()
 * @method string getCreatedAt()
 * 
 * @method Diglin_Intrum_Model_Log setLogId(int $logId)
 * @method Diglin_Intrum_Model_Log setFirstname(string $firstname)
 * @method Diglin_Intrum_Model_Log setLastname(string $lastname)
 * @method Diglin_Intrum_Model_Log string setTown(string $town)
 * @method Diglin_Intrum_Model_Log setStreet(string $street)
 * @method Diglin_Intrum_Model_Log setCountry(string $country)
 * @method Diglin_Intrum_Model_Log setPostcode(string $postcode)
 * @method Diglin_Intrum_Model_Log setIp(string $ip)
 * @method Diglin_Intrum_Model_Log setStatus(string $status)
 * @method Diglin_Intrum_Model_Log setRequestId(string $requestId)
 * @method Diglin_Intrum_Model_Log setType(string $type)
 * @method Diglin_Intrum_Model_Log setError(string $error)
 * @method Diglin_Intrum_Model_Log setResponse(string $response)
 * @method Diglin_Intrum_Model_Log setRequest(string $request)
 * @method Diglin_Intrum_Model_Log setCreatedAt(string $createdAt)
 */
class Diglin_Intrum_Model_Log extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('diglin_intrum/log');
    }
}