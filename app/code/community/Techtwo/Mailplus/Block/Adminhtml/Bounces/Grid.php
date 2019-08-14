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

class Techtwo_Mailplus_Block_Adminhtml_Bounces_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
		public function __construct()
		{
			parent::__construct();
			$this->setId('bouncesGrid');
			// This is the primary key of the database
			$this->setDefaultSort('id');
			$this->setDefaultDir('ASC');
			$this->setSaveParametersInSession(true);
		}
		
		protected function _prepareCollection()
		{
			$collection = Mage::getModel('mailplus/bounce')->getCollection();
			//$collection = Mage::helper('mailplus')->joinEavTablesIntoCollection($collection, 'customer_id', 'customer');
			$this->setCollection($collection);
			
			return parent::_prepareCollection();
		}
		
		protected function _prepareMassaction()
		{
			$this->setMassactionIdField('queue_id');
			$this->getMassactionBlock()->setFormFieldName('mailplus_bounce_mass');

			$this->getMassactionBlock()->addItem('delete', array(
					'label' => Mage::helper('mailplus')->__('Delete'),
					'url' => $this->getUrl('*/*/massDelete'),
					'confirm' => Mage::helper('mailplus')->__('Are you sure you want to delete these bounces?')
			));
		}

		protected function _prepareColumns()
		{
				$this->addColumn('id', array(
					'header'    => Mage::helper('mailplus')->__('Id'),
					'align'     =>'right',
					'width'     => '50px',
					'index'     => 'id',
				));
				
				$this->addColumn('firstname', array(
					'header'    => Mage::helper('customer')->__('Firstname'),
					'index'     => 'firstname'
				));
						
				$this->addColumn('lastname', array(
					'header'    => Mage::helper('customer')->__('Lastname'),
					'index'     => 'lastname'
				));
						
				$this->addColumn('email', array(
					'header'    => Mage::helper('customer')->__('Email'),
					'index'     => 'email'
				));
				
				$this->addColumn('mailplus_id', array(
					'header'    => 'Mailplus id',
					'index'     => 'mailplus_id'
				));
				
				$this->addColumn('total_received', array(
						'header'    => Mage::helper('mailplus')->__('Total received'),
						'align'     =>'center',
						//'type'      => 'int',
						'index'     => 'total_received',
				));

				$this->addColumn('is_test', array(
						'header'    => Mage::helper('mailplus')->__('Is test'),
						'align'     =>'center',
						'type'      => 'bool',
						'index'     => 'is_test',
				));
				
				$this->addColumn('last_bounce_date', array(
						'header'    => Mage::helper('mailplus')->__('Date last bounced'),
						'align'     =>'center',
						'type'      => 'datetime',
						'index'     => 'last_bounce_date',
				));

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
			return '#';
			return $this->getUrl('*/*/edit', array('id' => $row->getId()));
		}
}