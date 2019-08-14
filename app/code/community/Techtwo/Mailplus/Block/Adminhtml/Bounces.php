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
class Techtwo_Mailplus_Block_Adminhtml_Bounces extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_bounces';
		$this->_blockGroup = 'mailplus';
		$this->_headerText = Mage::helper('mailplus')->__('MailPlus Bounces');
		parent::__construct();
		
		if ( false) // not yet supported
		{
			$this->_addButton('add', array(
				'label'     => 'Add Bounce',
				//'onclick'   => 'submitMyHooks(\'' . Mage::helper('adminhtml')->getUrl('mailplus/adminhtml_index/new') . '\')',
				'class'     => 'add'
			));
		}
		else
			$this->_removeButton('add');
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
 }