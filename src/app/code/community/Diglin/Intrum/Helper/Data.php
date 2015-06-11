<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin_Intrum
 * @package     Diglin_Intrum
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

use Diglin\Intrum\CreditDecision\Request;
use Diglin\Intrum\CreditDecision\Response;

/**
 * Class Diglin_Intrum_Helper_Data
 */
class Diglin_Intrum_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request $request
     * @param $xmlRequest
     * @param $xmlResponse
     * @param $status
     * @param $type
     * @throws Exception
     * @return $this
     */
    public function saveLog(Request $request, $xmlRequest, $xmlResponse, $status, $type)
    {
        $companyName = '';
        if ($person = $request->getCustomer()->getPerson()) {
            $address = $person->getCurrentAddress();
        } else if ($company = $request->getCustomer()->getCompany()) {
            $companyName = $company->getCompanyName1();
            $orderingPerson = $company->getOrderingPerson();

            /* @var $person Diglin\Intrum\CreditDecision\Request\Customer\Person */
            $person = $orderingPerson['OrderingPerson']['Person'];
            $address = $company->getCurrentAddress();
        }

        $data = array('firstname'  => $person->getFirstName(),
                      'lastname'   => $person->getLastName(),
                      'company'    => $companyName,
                      'postcode'   => $address->getPostCode(),
                      'town'       => $address->getTown(),
                      'country'    => $address->getCountryCode(),
                      'street'     => $address->getFirstLine(),
                      'request_id' => $request->getRequestId(),
                      'status'     => ($status != 0) ? $status : 'Error',
                      'error'      => '',
                      'request'    => $xmlRequest,
                      'response'   => $xmlResponse,
                      'type'       => $type,
                      'ip'         => $_SERVER['REMOTE_ADDR']);

        $intrumModel = Mage::getModel('diglin_intrum/log');
        $intrumModel
            ->setData($data)
            ->save();

        return $this;
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipAddress = 'UNKNOWN';
        }

        return $ipAddress;
    }

    /**
     * @param $value
     * @return string
     */
    public function valueToStatus($value)
    {
        $status[0] = $this->__('Fail to connect (status Error)');
        $status[1] = $this->__('There are serious negative indicators (status 1)');
        $status[2] = $this->__('All payment methods allowed (status 2)');
        $status[3] = $this->__('Manual post-processing (currently not yet in use) (status 3)');
        $status[4] = $this->__('Postal address is incorrect (status 4)');
        $status[5] = $this->__('Enquiry exceeds the credit limit (the credit limit is specified in the cooperation agreement) (status 5)');
        $status[6] = $this->__('Customer specifications not met (optional) (status 6)');
        $status[7] = $this->__('Enquiry exceeds the net credit limit (enquiry amount plus open items exceeds credit limit) (status 7)');
        $status[8] = $this->__('Person queried is not of creditworthy age (status 8)');
        $status[9] = $this->__('Delivery address does not match invoice address (for payment guarantee only) (status 9)');
        $status[10] = $this->__('Household cannot be identified at this address (status 10)');
        $status[11] = $this->__('Country is not supported (status 11)');
        $status[12] = $this->__('Party queried is not a natural person (status 12)');
        $status[13] = $this->__('System is in maintenance mode (status 13)');
        $status[14] = $this->__('Address with high fraud risk (status 14)');
        $status[15] = $this->__('Allowance is too low (status 15)');

        if (isset($status[$value])) {
            return $status[$value];
        }

        return $status[0];
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $intrumStatus
     * @param Response $intrumResponse
     * @return $this
     * @throws Exception
     */
    public function saveStatusToOrder(Mage_Sales_Model_Order $order, $intrumStatus, Response $intrumResponse)
    {
        $comment = $this->__('<strong>Intrum status: %s</strong>', $this->valueToStatus($intrumStatus));
        $comment .= '<br>';
        $comment .= $this->__('<strong>Credit Rating: %s</strong>', $intrumResponse->getCustomerCreditRating());
        $comment .= '<br>';
        $comment .= $this->__('<strong>Credit rating level: %s</strong>', $intrumResponse->getCustomerCreditRatingLevel());
        $comment .= '<br>';
        $comment .= $this->__('<strong>Status code: %s</strong>', $intrumStatus);

        $order->addStatusHistoryComment($comment);

        $order->setIntrumStatus($intrumStatus);
        $order->setIntrumCreditRating($intrumResponse->getCustomerCreditRating());
        $order->setIntrumCreditLevel($intrumResponse->getCustomerCreditRatingLevel());
        $order->save();

        return $this;
    }

    /**
     * Create Request
     *
     * @param $object
     * @param null $paymentMethod
     * @return DOMDocument
     * @throws Exception
     */
    public function createRequest($object, $paymentMethod = null)
    {
        if (!$object instanceof Mage_Sales_Model_Order && !$object instanceof Mage_Sales_Model_Quote) {
            throw new Exception($this->__('Object is not an order or a quote'));
        }

        if (!$object->getBillingAddress()->getFirstname()
            || !$object->getBillingAddress()->getLastname()
            || !$object->getBillingAddress()->getStreetFull()
            || !$object->getBillingAddress()->getPostcode()
            || !$object->getBillingAddress()->getCity()
            || !$object->getBillingAddress()->getCountryId()
        ) {
            return false;
        }

        $store = $object->getStore();
        $dom = new \DOMDocument("1.0", "UTF-8");

        $this->request = new Request();
        $dom->appendChild($this->request);
        $this->request->setClientId(Mage::getStoreConfig('intrum/api/clientid', $store));
        $this->request->setUserID(Mage::getStoreConfig('intrum/api/userid', $store));
        $this->request->setPassword(Mage::getStoreConfig('intrum/api/password', $store));
        $this->request->setRequestId((int)$object->getBillingAddress()->getId() . time());

        try {
            $this->request->setEmail(Mage::getStoreConfig('intrum/api/mail', $store));
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $birthday = $object->getCustomerDob();
        if (!empty($birthday)) {
            $birthday = Mage::getModel('core/date')->date('Y-m-d', strtotime($birthday));
        }

        if ($object instanceof Mage_Sales_Model_Order) {
            $customerId = $object->getCustomerId();
        } else {
            $customerId = $object->getCustomer()->getId();
        }

        if (empty($customerId)) {
            $reference = "guest_" . $object->getBillingAddress()->getId();
        } else {
            $reference = $object->getCustomer()->getId();
        }

        $data = array(
            'customer_reference' => $reference
        );

        $person = array(
            'first_name'            => (string)$object->getBillingAddress()->getFirstname(),
            'last_name'             => (string)$object->getBillingAddress()->getLastname(),
            'gender'                => $object->getCustomerGender(),
            'date_of_birth'         => $birthday, // YYYY-MM-DD
            'language'              => (string)substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2),
            'current_address'       => array(
                'first_line'   => trim((string)$object->getBillingAddress()->getStreetFull()),
                'post_code'    => (string)$object->getBillingAddress()->getPostcode(),
                'country_code' => (string)$object->getBillingAddress()->getCountryId(),
                'town'         => (string)$object->getBillingAddress()->getCity()
            ),
            'communication_numbers' => array(
                'telephone_private' => (string)$object->getBillingAddress()->getTelephone(),
                'email'             => (string)$object->getBillingAddress()->getEmail(),
                'fax'               => (string)$object->getBillingAddress()->getFax()
            )
        );

        $company = $object->getBillingAddress()->getCompany();
        if (empty($company)) {
            $data['person'] = $person;
            $data['person']['extra_info'] = $this->getExtraInfo($object, $paymentMethod);
        } else {
            $data['company'] = array(
                'company_name1'   => $company,
                'current_address' => array(
                    'first_line'   => trim((string)$object->getBillingAddress()->getStreetFull()),
                    'post_code'    => (string)$object->getBillingAddress()->getPostcode(),
                    'country_code' => (string)$object->getBillingAddress()->getCountryId(),
                    'town'         => (string)$object->getBillingAddress()->getCity()
                ),
                'ordering_person' =>
                    array(
                        'function' => 1, //CEO
                        'person'   => $person
                    ),
                'extra_info' => $this->getExtraInfo($object, $paymentMethod)
            );
        }

        $this->request->createRequest($data);

        return $dom;
    }

    /**
     * @param Mage_Sales_Model_Order | Mage_Sales_Model_Quote $object
     * @param string $paymentMethod
     * @return array
     */
    public function getExtraInfo($object, $paymentMethod)
    {
        $extraInfo = array(
            array(
                'name'  => 'ORDERCLOSED',
                'value' => ($object instanceof Mage_Sales_Model_Order) ? 'YES' : 'NO'
            ),
            array(
                'name'  => 'ORDERAMOUNT',
                'value' => $object->getGrandTotal()
            ),
            array(
                'name'  => 'ORDERCURRENCY',
                'value' => $object->getBaseCurrencyCode()
            ),
            array(
                'name'  => 'IP',
                'value' => $this->getClientIp()
            )
        );

        if ($object instanceof Mage_Sales_Model_Quote) {
            $canShip = ($object->isVirtual()) ? false : true;
        } elseif ($object instanceof Mage_Sales_Model_Order) {
            $canShip = ($object->canShip()) ? true : false;
        }

        /* Shipping information */
        if ($canShip) {
            $extraInfo = array_merge($extraInfo, array(
                array(
                    'name'  => 'DELIVERY_FIRSTNAME',
                    'value' => $object->getShippingAddress()->getFirstname(),
                ),
                array(
                    'name'  => 'DELIVERY_LASTNAME',
                    'value' => $object->getShippingAddress()->getLastname(),
                ),
                array(
                    'name'  => 'DELIVERY_FIRSTLINE',
                    'value' => $object->getShippingAddress()->getStreetFull(),
                ),
                array(
                    'name'  => 'DELIVERY_HOUSENUMBER',
                    'value' => '',
                ),
                array(
                    'name'  => 'DELIVERY_COUNTRYCODE',
                    'value' => strtoupper($object->getShippingAddress()->getCountry()),
                ),
                array(
                    'name'  => 'DELIVERY_POSTCODE',
                    'value' => strtoupper($object->getShippingAddress()->getPostcode()),
                ),
                array(
                    'name'  => 'DELIVERY_TOWN',
                    'value' => strtoupper($object->getShippingAddress()->getCity()),
                )
            ));
        }

        if ($object instanceof Mage_Sales_Model_Order) {
            $extraInfo[] = array(
                'name'  => 'ORDERID',
                'value' => $object->getIncrementId(),
            );

            if (!empty($paymentMethod)) {
                $extraInfo[] = array(
                    'name'  => 'PAYMENTMETHOD',
                    'value' => $this->mapPaymentMethodToSpecs($paymentMethod),
                );
            }
        }

        return $extraInfo;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return DOMDocument
     * @throws Exception
     */
    public function createShopRequest(Mage_Sales_Model_Quote $quote)
    {
        return $this->createRequest($quote);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $paymentMethod
     * @return DOMDocument
     * @throws Exception
     */
    function createShopRequestPaid(Mage_Sales_Model_Order $order, $paymentMethod)
    {
        return $this->createRequest($order, $paymentMethod);
    }

    /**
     * @param $paymentCode
     * @return mixed|string
     */
    public function mapPaymentMethodToSpecs($paymentCode)
    {
        $mapping = Mage::getStoreConfig('intrum/mappings/group_' . $paymentCode, Mage::app()->getStore());
        if (empty($mapping)) {
            $mapping = 'INVOICE';
        }

        return $mapping;
    }

    /**
     * @param string $string
     * @return array
     */
    public function getAllowedAndDeniedMethods($string)
    {
        $methods = array();
        $methods["allowed"] = array();
        $methods["denied"] = array();
        $str = explode(",", $string);
        foreach ($str as $strval) {
            $m = explode("_", $strval);
            $last = end($m);
            $method = str_replace("_" . $last, "", $strval);
            if ($last == 'allow') {
                $methods["allowed"][] = $method;
            } else {
                $methods["denied"][] = $method;
            }
        }

        return $methods;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
