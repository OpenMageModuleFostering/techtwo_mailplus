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
*/class Techtwo_Mailplus_Block_Adminhtml_Restqueue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('RestqueueGrid');
		$this->setDefaultSort('id');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection()
	{
		$collection = Mage::getModel('mailplus/restqueue')->getCollection();
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}
		
	protected function _prepareColumns()
	{
		$this->addColumn('queue_id', array(
			'header'    => Mage::helper('mailplus')->__('queue_id'),
			'align'     =>'right',
			'width'     => '50px',
			'index'     => 'queue_id',
			'type'      => 'range'
		));

		$this->addColumn('created_at', array(
				'header'    => Mage::helper('mailplus')->__('Created'),
				'align'     => 'left',
				'index'     => 'created_at',
				'type'		=> 'date'
		));
		
		$this->addColumn('last_run_at', array(
				'header'    => Mage::helper('mailplus')->__('Last run'),
				'align'     => 'left',
				'index'     => 'last_run_at',
				'type'		=> 'date'
		));
		
		$this->addColumn('method', array(
				'header'    => Mage::helper('mailplus')->__('Method'),
				'align'     =>'left',
				'index'     => 'method',
				'width'     => '50px'
		));
		
		$this->addColumn('url', array(
				'header'    => Mage::helper('mailplus')->__('URL'),
				'align'     =>'left',
				'index'     => 'url'
		));
		
		$this->addColumn('tries', array(
				'header'    => Mage::helper('mailplus')->__('Tries'),
				'align'     =>'left',
				'index'     => 'tries',
				'type='		=> 'number',
				'width'     => '50px'
		));
		
		$this->addColumn('last_error', array(
				'header'    => Mage::helper('mailplus')->__('Last error'),
				'align'     =>'left',
				'index'     => 'last_error'
		));
		
		$this->addColumn('last_response', array(
				'header'    => Mage::helper('mailplus')->__('Last response'),
				'align'     =>'left',
				'index'     => 'last_response'
		));
		
		return parent::_prepareColumns();
	}

	public function getRowUrl($row)
	{
		return;
	}
	
	public function getGridUrl()
	{
		return $this->getUrl('*/*/grid', array('_current'=>true));
	}
	
}