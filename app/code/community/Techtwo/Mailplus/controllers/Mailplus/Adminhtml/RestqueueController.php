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
class Techtwo_Mailplus_Mailplus_Adminhtml_RestqueueController extends Mage_Adminhtml_Controller_Action
{	
	public function indexAction()
	{	
		$this->loadLayout()->_setActiveMenu('mailplus')
			->_addContent($this->getLayout()->createBlock('mailplus/adminhtml_restqueue'))
			->renderLayout();
	}
	
}
