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
class Techtwo_Mailplus_Block_Adminhtml_Mapping extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		parent::__construct();
	
		$this->_objectId = 'id';
		$this->_blockGroup = 'mailplus';
		$this->_controller = 'adminhtml_mapping';
		 
		$this->_updateButton('save', 'label', Mage::helper('mailplus')->__('Save'));
		$this->_removeButton('reset');
		$this->_removeButton('back');
	}
	
	public function getHeaderText()
	{
		return Mage::helper('mailplus')->__('Mapping');
	}

 }