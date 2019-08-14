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
class Techtwo_Mailplus_Block_Adminhtml_Syncstatus extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_syncstatus';
		$this->_blockGroup = 'mailplus';
		$this->_headerText = Mage::helper('mailplus')->__('MailPlus sync status');
		
		parent::__construct();
		
		$this->_removeButton('add');
		$this->_addButton('reload', array(
				'label'     => Mage::helper('adminhtml')->__('Refresh'),
				'onclick'   => 'setLocation(window.location.href)',
		), -1);
	}
 }