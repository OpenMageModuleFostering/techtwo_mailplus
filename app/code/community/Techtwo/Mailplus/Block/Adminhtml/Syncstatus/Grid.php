<?php
/*
* Copyright 2014 MailPlus
*
* Licensed under the Apache License, Version 2.0 (the "License"); you may not
* use this file except in compliance with the License. You may obtain a copy
* of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
* 
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations
* under the License.
*/
class Techtwo_Mailplus_Block_Adminhtml_Syncstatus_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct() {
		parent::__construct();
		$this->setId('SyncstatusGrid');
		$this->setFilterVisibility(false);
		$this->setPagerVisibility(false);
	}
	
	protected function _prepareCollection()
	{
		/* @var $mailplus Techtwo_Mailplus_Helper_Data */
		$mailplus = Mage::helper('mailplus');
		$website = $mailplus->getWebsiteFromRequest();
		
		$collection = new Varien_Data_Collection();
        
		$data = new Varien_Object();
        $data->setName($mailplus->__('Newsletter subscribers'));
        $data->setCount($mailplus->getSyncCount($website->getId(), Techtwo_Mailplus_Model_Syncqueue::TYPE_SUBSCRIBER));
        $data->setSynctype(Techtwo_Mailplus_Model_Syncqueue::TYPE_SUBSCRIBER);
        $collection->addItem($data);

        $data = new Varien_Object();
        $data->setName($mailplus->__('Customers'));
        $data->setCount($mailplus->getSyncCount($website->getId(), Techtwo_Mailplus_Model_Syncqueue::TYPE_CUSTOMER));
        $data->setSynctype(Techtwo_Mailplus_Model_Syncqueue::TYPE_CUSTOMER);
        $collection->addItem($data);
        
        $data = new Varien_Object();
        $data->setName($mailplus->__('Products'));
        $data->setCount($mailplus->getSyncCount($website->getId(), Techtwo_Mailplus_Model_Syncqueue::TYPE_PRODUCT));
        $data->setSynctype(Techtwo_Mailplus_Model_Syncqueue::TYPE_PRODUCT);
        $collection->addItem($data);
        
        $data = new Varien_Object();
        $data->setName($mailplus->__('Orders'));
        $data->setCount($mailplus->getSyncCount($website->getId(), Techtwo_Mailplus_Model_Syncqueue::TYPE_ORDER));
        $data->setSynctype(Techtwo_Mailplus_Model_Syncqueue::TYPE_ORDER);
        $collection->addItem($data);
        
		$this->setCollection($collection);
	}
		
	protected function _prepareColumns()
	{
		/* @var $mailplus Techtwo_Mailplus_Helper_Data */
		$mailplus = Mage::helper('mailplus');
		$website = $mailplus->getWebsiteFromRequest();
		
		$this->addColumn('name', array(
			'header'    => Mage::helper('mailplus')->__('Name'),
			'align'     =>'left',
			'width'     => '50px',
			'index'     => 'name'
		));

		$this->addColumn('count', array(
				'header'    => Mage::helper('mailplus')->__('Count'),
				'align'     =>'left',
				'width'     => '50px',
				'index'     => 'count'
		));		
		
		$this->addColumn('action', array(
				'header' => Mage::helper('mailplus')->__('Action'),
				'width' =>  '50px',
				'type' => 'action',
				'getter' => 'getSynctype',
				'actions' => array(
					array(
						'caption' => 'Sync',
						'url' => array(
							'base' => '*/*/sync',
							'params' => array('website' => $website->getCode())
						),
						'field' => 'synctype'		
					)
				)
		));
				
		
		
		return parent::_prepareColumns();
	}

	public function getGridUrl() {
		return $this->getUrl('*/*/grid', array('_current'=>true));
	}

}