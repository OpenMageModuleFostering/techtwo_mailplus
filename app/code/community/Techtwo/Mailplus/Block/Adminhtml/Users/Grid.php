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
class Techtwo_Mailplus_Block_Adminhtml_Users_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
		public function __construct()
		{
			parent::__construct();
			$this->setId('usersGrid');
			// This is the primary key of the database
			$this->setDefaultSort('user_id');
			$this->setDefaultDir('ASC');
			$this->setSaveParametersInSession(true);
			$this->setTemplate('mailplus/users/grid.phtml');
		}

		protected function _prepareCollection()
		{
			$collection = Mage::getModel('mailplus/user')->getCollection();
			//$collection = Mage::helper('mailplus')->joinEavTablesIntoCollection($collection, 'customer_id', 'customer');
			/* @var $collection Techtwo_Mailplus_Model_Mysql4_User_Collection */
			$this->setCollection($collection);
			
			return parent::_prepareCollection();
		}
		
		protected function _prepareMassaction()
		{
			$this->setMassactionIdField('user_id');
			$this->getMassactionBlock()->setFormFieldName('mailplus_user_mass');

			$this->getMassactionBlock()->addItem('delete', array(
					'label' => Mage::helper('mailplus')->__('Delete'),
					'url' => $this->getUrl('*/*/massDelete'),
					'confirm' => Mage::helper('mailplus')->__('Are you sure you want to delete these contacts?')
			));
		}

		protected function _prepareColumns()
		{
				$this->addColumn('user_id', array(
					'header'    => Mage::helper('mailplus')->__('External id'),
					'align'     =>'right',
					'width'     => '50px',
					'index'     => 'user_id',
					'type'      => 'range'
				));
				
				$this->addColumn('customer_id', array(
					'header'    => Mage::helper('mailplus')->__('Customer id'),
					'align'     =>'right',
					'width'     => '50px',
					'index'     => 'customer_id',
					'type'      => 'range'
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
				/*
				$this->addColumn('mailplus_id', array(
					'header'    => 'Mailplus id',
					'index'     => 'mailplus_id',
					'width'     => '160px'
				));
				*/

				$this->addColumn('enabled', array(
						'header'    => Mage::helper('mailplus')->__('Enabled'),
						'align'     =>'center',
						'type'      => 'bool',
						'index'     => 'enabled',
						'width'     => '50px',
				));

				if (!Mage::app()->isSingleStoreMode()) {

					/* @var $systemStore Mage_Adminhtml_Model_System_Store */
					$systemStore = Mage::getSingleton('adminhtml/system_store');

					$this->addColumn('store_id', array(
						'header'    => Mage::helper('customer')->__('Store'),
						'align'     => 'center',
						'width'     => '100px',
						'type'      => 'options',
						'options'   => $systemStore->getStoreOptionHash(TRUE), // getWebsiteOptionHash(TRUE),
						'index'     => 'store_id',
					));
				}

				/*
				$this->addColumn('content', array(
						'header'    => Mage::helper('<module>')->__('Item Content'),
						'width'     => '150px',
						'index'     => 'content',
				));


				$this->addColumn('created_time', array(
						'header'    => Mage::helper('<module>')->__('Creation Time'),
						'align'     => 'left',
						'width'     => '120px',
						'type'      => 'date',
						'default'   => '--',
						'index'     => 'created_time',
				));

				$this->addColumn('update_time', array(
						'header'    => Mage::helper('<module>')->__('Update Time'),
						'align'     => 'left',
						'width'     => '120px',
						'type'      => 'date',
						'default'   => '--',
						'index'     => 'update_time',
				));   


				$this->addColumn('status', array(

						'header'    => Mage::helper('<module>')->__('Status'),
						'align'     => 'left',
						'width'     => '80px',
						'index'     => 'status',
						'type'      => 'options',
						'options'   => array(
								1 => 'Active',
								0 => 'Inactive',
						),
				));
		*/

				return parent::_prepareColumns();
		}

		public function getRowUrl($row)
		{
				return $this->getUrl('*/*/edit', array('id' => $row->getUserId()));
		}
}