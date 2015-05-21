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
 * Class Diglin_Intrum_Block_Admin_Log_Grid
 */
class Diglin_Intrum_Block_Admin_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        $this->setId('intrumGrid');
        $this->setDefaultSort('log_id');
        $this->setDefaultDir('DESC');
        parent::__construct();
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('diglin_intrum/log_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    public function getRowUrl($row)
    {
        // This is where our row data will link to
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _prepareColumns()
    {

        $this->addColumn('log_id', array(
            'header'       => Mage::helper('diglin_intrum')->__('ID'),
            'align'        => 'right',
            'width'        => '50px',
            'index'        => 'log_id',
        ));
        $this->setDefaultSort('intrum_id');
        $this->setDefaultDir('desc');

        $this->addColumn('request_id', array(
            'header'       => Mage::helper('diglin_intrum')->__('Request ID'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'request_id',
            'type'         => 'text',
            'truncate'     => 50,
            'escape'       => true,
        ));

        $this->addColumn('type', array(
            'header'       => Mage::helper('diglin_intrum')->__('Request type'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'type',
            'type'         => 'text',
            'truncate'     => 50,
            'escape'       => true,
        ));

        $this->addColumn('firstname', array(
            'header'       => Mage::helper('diglin_intrum')->__('Firstname'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'firstname',
            'type'         => 'text',
            'truncate'     => 50,
            'escape'       => true,
        ));

        $this->addColumn('lastname', array(
            'header'       => Mage::helper('diglin_intrum')->__('Lastname'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'lastname',
            'type'         => 'text',
            'truncate'     => 50,
            'escape'       => true,
        ));

        $this->addColumn('company', array(
            'header'       => Mage::helper('diglin_intrum')->__('Company'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'company',
            'type'         => 'text',
            'truncate'     => 50,
            'escape'       => true,
        ));

        $this->addColumn('ip', array(
            'header'       => Mage::helper('diglin_intrum')->__('IP'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'ip',
            'type'         => 'text',
            'truncate'     => 50,
            'escape'       => true,
        ));

        $this->addColumn('status', array(
            'header'       => Mage::helper('diglin_intrum')->__('Status'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'status',
            'type'         => 'text',
            'truncate'     => 50,
            'escape'       => true,
        ));

        $this->addColumn('created_at', array(
            'header'       => Mage::helper('diglin_intrum')->__('Date'),
            'align'        => 'left',
            'width'        => '150px',
            'index'        => 'created_at',
            'type'         => 'datetime',
            'escape'       => true,
        ));

        return parent::_prepareColumns();
    }
}
