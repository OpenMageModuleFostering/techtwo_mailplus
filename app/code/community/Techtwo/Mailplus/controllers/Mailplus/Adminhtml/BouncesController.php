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
class Techtwo_Mailplus_Mailplus_Adminhtml_BouncesController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction()
	{
		$this->loadLayout()->_setActiveMenu('mailplus')
			->_addContent($this->getLayout()->createBlock('mailplus/adminhtml_bounces'))
			->renderLayout();
		return;
	}
	
	public function massDeleteAction()
	{
		$bounces_to_delete = $this->_request->getParam('mailplus_bounce_mass');
		
		$bounceModel = Mage::getModel('mailplus/bounce');
		foreach ( $bounces_to_delete as $id )
		{
			$id = (int) $id;
			$bounceModel->load($id);
			if ( $bounceModel->getId() )
				$bounceModel->delete();
		}
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mailplus')->__('Bounce was successfully deleted'));
		$this->_redirect('*/*/');
	}

	public function editAction()
	{
		/*
		  $this->loadLayout()->_setActiveMenu('newsletter')
		  //->_addContent($this->getLayout()->createBlock('mailplus/admin_mailplus_edit')) // dynamic add content
		  ->renderLayout();
		 */

		$userId = $this->getRequest()->getParam('id');
		$userModel = Mage::getModel('mailplus/user')->load($userId);
		

		if ($userModel->getId() || $userId == 0)
		{

			Mage::register('mailplus_user_data', $userModel);

			$this->loadLayout();
			$this->_setActiveMenu('mailplus');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('mailplus/adminhtml_users_edit'))
							->_addLeft($this->getLayout()->createBlock('mailplus/adminhtml_users_edit_tabs'));

			$this->renderLayout();
		}
		else
		{
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mailplus')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}
	}
	
	public function saveAction()
	{
		
		$data = $this->getRequest()->getPost();
		if (!$data)
		{
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mailplus')->__('Unable to find mailplus user to save'));
			$this->_redirect('*/*/');
			return;
		}
		
		if (array_key_exists('mailplus_id', $data))
			unset($data['mailplus_id']);
		
		$customer = Mage::getModel('customer/customer');
		if ( '0' != $data['customer_id'] && $customer->load($data['customer_id'], 'customer_id')->getId() < 1 )
		{
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mailplus')->__('Unable to find customer to save'));
			Mage::getSingleton('adminhtml/session')->setFormData($data);
			$this->_redirect('*/*/edit/', array( 'id' => $this->getRequest()->id ));
			return;
		}
		
		$model = Mage::getModel('mailplus/user');
		$model->setData($data)
			->setId($this->getRequest()->getParam('id'));
		
		try
		{
			$model->save();
			
			$properties = array( );
			if ( '1' == $data['is_test'] )
				$properties['testGroup'] = 'Ja';
			else
				$properties['testGroup'] = 'Nee';
			
			/* MailPlus_Queue::add( 'contacts/updateContact', array(
						'externalContactId' => $model->getId(),
						'keys'     => array_keys($properties),
						'values'   => array_values($properties),
						'visible'  => '0' != $data['enabled'],
						'merge'    => true
					),
					$data['customer_id']
			); */

			// @TODO: Check what this should do, or if it should be removed
			
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mailplus')->__('User was succesful saved'));
			Mage::getSingleton('adminhtml/session')->setFormData(false);

			if ($this->getRequest()->getParam('back')) {
				$this->_redirect('*/*/edit', array('id' => $model->getCustomerId()));
				return;
			}
			$this->_redirect('*/*/');
			return;
		} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
		}
	}
	
	/* 
	 * @deprecated
	 * 
	*/
	public function deleteAction()
	{
		$model = Mage::getModel('mailplus/user')->load($this->getRequest()->get('id'));
		if ( $model->getId() < 1 )
		{
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mailplus')->__('Unable to find mailplus user to delete on id '.$this->getRequest()->get('id') ));
			$this->_redirect('*/*/');
			return;
		}
		
		if ( 0 != $model->getData('customer_id') )
		{
			$customer = Mage::getModel('customer/customer')->load($model->getData('customer_id'));
			if ( $customer->getId() < 1 )
				$customer = NULL;
		}
		else
			$customer = NULL;
		
		
		if ( $customer )
			$status = sprintf(Mage::helper('mailplus')->__('User id %s ( customer %s ) ( mailplus  %s ) was succesful deleted'), $model->getData('user_id'), $customer->getData('firstname').' '.$customer->getData('middlename').' '.$customer->getData('lastname'), $model->getData('mailplus_id'));
		else
			$status = sprintf(Mage::helper('mailplus')->__('User id %s ( mailplus  %s ) was succesful deleted'), $model->getData('user_id'), $model->getData('mailplus_id'));
		
		try {
			$model->delete();
			
				
			Mage::getSingleton('adminhtml/session')->addSuccess('Please be aware there is a certain delay in Mailplus');
		}
		catch (Exception $ex)
		{
			$status = 'Error occurred : '.$ex->getMessage();
		}
		
		
		Mage::getSingleton('adminhtml/session')->addSuccess($status);
		$this->_redirect('*/*');
	}

	/*
	  protected function _addContent(Mage_Core_Block_Abstract $block)
	  {
	  $this->getLayout()->getBlock('content')->append($block);
	  return $this;
	  } */
}