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
class Techtwo_Mailplus_Block_Adminhtml_Users_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

	protected function _prepareForm()
	{
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('mailplus_form', array('legend' => Mage::helper('mailplus')->__('Customer information')));

		/*
		$fieldset->addField('mailplus_id', 'label', array(
				'label'     => Mage::helper('mailplus')->__('Mailplus').' (<small>encrypted id</small>) : ',
				'class'     => 'entry',
				'name'      => 'mailplus_id',
		));
		*/
		
		$fieldset->addField('customer_id', 'text', array(
				'label' => Mage::helper('mailplus')->__('Customer'),
				'class' => 'entry', // required-entry : blank is now used as anonymous
				// 'required' => true,
				'name' => 'customer_id',
		));
		

		/*
		$fieldset->addField('enabled', 'select', array(
				'label' => Mage::helper('mailplus')->__('Enabled'),
				'class' => 'entry',
				'required' => false,
				'name' => 'enabled',
				'values' => array(
						array(
								'value' => 0,
								'label' => Mage::helper('mailplus')->__('No'),
						),
						array(
								'value' => 1,
								'label' => Mage::helper('mailplus')->__('Yes'),
						),
				),
		));
		*/

		$fieldset->addField('is_test', 'select', array(
				'label' => Mage::helper('mailplus')->__('Is test account'),
				'class' => 'entry',
				'required' => false,
				'name' => 'is_test',
				'values' => array(
						array(
								'value' => 0,
								'label' => Mage::helper('mailplus')->__('No'),
						),
						array(
								'value' => 1,
								'label' => Mage::helper('mailplus')->__('Yes'),
						),
				),
		));


		/*
		$fieldset->addField('status', 'select', array(
				'label' => Mage::helper('mailplus')->__('Status'),
				'name' => 'status',
				'values' => array(
						array(
								'value' => 1,
								'label' => Mage::helper('mailplus')->__('Active'),
						),
						array(
								'value' => 0,
								'label' => Mage::helper('mailplus')->__('Inactive'),
						),
				),
		));

		$fieldset->addField('content', 'editor', array(
				'name' => 'content',
				'label' => Mage::helper('mailplus')->__('Content'),
				'title' => Mage::helper('mailplus')->__('Content'),
				'style' => 'width:98%; height:400px;',
				'wysiwyg' => false,
				'required' => true,
		));
		*/


		if (Mage::getSingleton('adminhtml/session')->getMailplusUserData())
		{
			$form->setValues(Mage::getSingleton('adminhtml/session')->getMailplusUserData());
			Mage::getSingleton('adminhtml/session')->setMailplusUserData(null);
		}
		elseif (Mage::registry('mailplus_user_data'))
		{
			$form->setValues(Mage::registry('mailplus_user_data')->getData());
			// print_r(Mage::registry('mailplus_user_data')->getData());
		}
		return parent::_prepareForm();
	}

}