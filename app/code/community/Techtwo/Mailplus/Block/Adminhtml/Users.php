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
class Techtwo_Mailplus_Block_Adminhtml_Users extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_users';
		$this->_blockGroup = 'mailplus';
		$this->_headerText = Mage::helper('mailplus')->__('User manager');
		parent::__construct();
		
		if ( false) // not yet supported
		{
			$this->_addButton('add', array(
				'label'     => 'Add User',
				//'onclick'   => 'submitMyHooks(\'' . Mage::helper('adminhtml')->getUrl('mailplus/adminhtml_index/new') . '\')',
				'class'     => 'add'
			));
		}
		else
			$this->_removeButton('add');
		
		
		$this->_addButton('import', array(
			'label'     => Mage::helper('mailplus')->__('Import users'),
			'onclick'   => "document.location.href='".Mage::getSingleton('adminhtml/url')->getUrl('*/*/import')."'",
			'class'     => 'add'
		));
		
		$this->_addButton('export', array(
			'label'     => Mage::helper('mailplus')->__('Export users'),
			'onclick'   => "document.location.href='".Mage::getSingleton('adminhtml/url')->getUrl('*/*/export')."'",
			'class'     => 'add'
		));
	}

	/*
	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		if(!$this->getRequest()->isXmlHttpRequest()){
			$this->getLayout()->getBlock('head')->addItem('skin_js', 'mailplus/Mailplus.js');
		}
	}
	*/
	
	
	protected function getMaxImportFilesize()
	{
		return 7*1024*1024; // 7 mb
	}
}