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
class Techtwo_Mailplus_UseController extends Mage_Core_Controller_Front_Action
{
	public function cartAction()
	{
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');
		$quote_id = $this->_request->getParam('id');
		/* @var $quote Mage_Sales_Model_Quote */
		$quote = Mage::getModel('sales/quote')->load($quote_id);
		if ($quote->getId())
		{
			/* @var $customerSession Mage_Customer_Model_Session */
			$customerSession = Mage::getSingleton('customer/session');
			$current_customer_id = NULL;
			if ( $customerSession->isLoggedIn() )
			{
				$current_customer_id = $customerSession->getCustomerId();
			}

			/**
			 * You are trying to resume a quote of a customer .. is but you are not logged in
			 * You have to authorize yourself if you want to resume from another customer quote
			 */
			if ( $quote->getCustomerId() && NULL === $current_customer_id )
			{
				/* @var $urlHelper Mage_Core_Helper_Url */
				$urlHelper = Mage::helper('core/url');

				$customerSession->setAfterAuthUrl( $urlHelper->getCurrentUrl() );
				Mage::getSingleton('core/session')->addError(
					$dataHelper->__('Please login to resume continue shopping with your last cart')
				);

				$this->_redirect('customer/account/login');
				return;
			}

			/**
			 * You are logged in, but you resume a quote from somebody else. That's a nono!
			 */
			if ( $quote->getCustomerId() && $quote->getCustomerId() !== $current_customer_id )
			{
				Mage::getSingleton('core/session')->addError(
					$dataHelper->__('Invalid cart quote')
				);
				$this->_redirect('/');
			}



			/* @var $checkoutSession Mage_Checkout_Model_Session */
			$checkoutSession = Mage::getSingleton('checkout/session');
			$checkoutSession->replaceQuote( $quote );

			if ( TRUE !== $quote->getIsActive() || 1 !== (int) $quote->getIsActive() )
			{
				$quote->setIsActive(TRUE);
				$quote->save();
			}

			Mage::getSingleton('core/session')->addSuccess(
				$dataHelper->__('Your cart is restored')
			);
			$this->_redirect('checkout/cart');
		}


	}
}