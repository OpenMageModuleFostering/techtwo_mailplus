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
class Techtwo_Mailplus_Observer_Customer
{
	const SESSION_CONVERSION_KEY = 'mailplus_registered_conversions';

	/**
	 * Login customer
	 *
	 * Checks if MailPlus had send a bounce error. If an error was found, the bounce will be informed.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function login_customer(Varien_Event_Observer $observer)
	{
		if ( ! Mage::getSingleton('customer/session')->isLoggedIn() )
			return;

		$customer = Mage::getSingleton('customer/session')->getCustomer();
		/* @var $customer Mage_Customer_Model_Customer */

		$bounces = Mage::getModel('mailplus/bounce')->getCollection();
		/* @var $bounces Techtwo_Mailplus_Model_Mysql4_Bounce_Collection */
		$bounces->getSelect()->where('email = ?', $customer->getData('email'))->limit(1)->order('last_bounce_date DESC');
		if ( $bounces->load()->count() > 0 )
		{
			$bounce = $bounces->getFirstItem();
			/* @var $bounce Techtwo_Mailplus_Model_Bounce */
				
			// WARNING! DO NOT PUT THIS CHECK IN THE SELECT !
			// WE HAVE USED THE SELECT SOLEY TO CHECK FOR A RECENT BOUNCE!
			// When you would put this in the select, it will switch the following record in the list...
			if ( '0' === $bounce->getData('is_customer_alerted') )
			{
				$bounce->setData('is_customer_alerted', '1');
				$bounce->save();

				$link = Mage::getUrl('customer/account/edit');
				Mage::getSingleton('core/session')->addError(
				sprintf(
				Mage::helper('mailplus')->__('Your email was not reachable at %2$s, please <a href=%1$s" title="goto account settings to edit your email">correct your email</a>')
				, $link, date('Y-m-d H:i', strtotime($bounce->getData('last_bounce_date')))
				)
				);
			}
		}
	}


	/**
	 * If someone was subscribed on the newsletter BEFORE he/ she became member
	 * and now registers himself/ herself without checking the newsletter
	 * Magento would still leave the subscription !
	 *
	 * This fixed the issue. When the checkbox is NOT set, you will be unsubscribed if subscribed.
	 * When that event occurs, an error will be showed to the user.
	 */
	public function register_customer( Varien_Event_Observer $observer )
	{
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');

		if ( !$dataHelper->isSynchronizeContactsAllowed() )
		{
			return $this;
		}

		if ( ! Mage::getSingleton('customer/session')->isLoggedIn() )
			return $this;

		/* @var $customer Mage_Customer_Model_Customer */
		$customer = Mage::getSingleton('customer/session')->getCustomer();

		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');

		/* @var $userModel Techtwo_Mailplus_Model_User */
		$userModel = Mage::getModel('mailplus/user');//->load( $customer->getEmail(), 'email' );
		$userModel = $userModel->findByEmail($customer->getEmail());
		
		if ($userModel->getId() < 1) {
			$userModel = $dataHelper->createUserFromCustomer($customer);
		} 
		
		$userModel->setCustomer($customer);
		
		if ('1' == Mage::app()->getFrontController()->getRequest()->getPost('is_subscribed', '0') ) {
			$userModel->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE );
		};
		
		$userModel->save();
		
		return $this;
	}


	/**
	 * Synchronizes the newsletter visitor ( if the subscriber is no customer )
	 * If the subscriber is a customer this method will just return 'true'.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool|Techtwo_Mailplus_Observer_Customer
	 * @throws Exception
	 */
	public function synchronize_visitor(Varien_Event_Observer $observer)
	{
		if (Mage::registry('mailplusModifiedContacts')) {
			// Dont sync back to MailPlus when getting updates from MailPlus
			return;
		}
		
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');

		if ( !$dataHelper->isSynchronizeContactsAllowed() )
		{
			return $this;
		}

		/* @var $subscriber Mage_Newsletter_Model_Subscriber */
		$subscriber = $observer->getEvent()->getData('subscriber');


		// Check if the subscriber is a customer, if so, the event for 'synchronize_customer' will deal with this
		if ( '' !== $subscriber->getData('customer_id') && '0' !== $subscriber->getData('customer_id') && ((int) $subscriber->getData('customer_id')) > 0 )
			return TRUE;

		/* @var $userModel Techtwo_Mailplus_Model_User */
		$userModel = Mage::getModel('mailplus/user')->findByEmail( $subscriber->getData('subscriber_email') );
		$status = $subscriber->getSubscriberStatus();

		$do_subscribe = $status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;

		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');


		// MailPlus is turned off
		if ( !Mage::app()->getStore()->isAdmin() && !$rest->getClientByStore(Mage::app()->getStore()->getId()) )
			return TRUE;

		try
		{
			if ( !$userModel->getId() && $do_subscribe )
			{
				$userModel->setData('customer_id' , NULL);
				$userModel->setData('mailplus_id' , NULL);  // always be filled by feedback
				$userModel->setData('enabled'     , 1);
				$userModel->setData('is_test'     , 0);
				$userModel->setData('email'       , $subscriber->getData('subscriber_email'));
				$userModel->setData('store_id'    , Mage::app()->getStore()->getId());
				$userModel->setData('createts'    , time());

				$userModel->save();
			}

			if ( $do_subscribe )
			{
				$rest->updateContactPermission($userModel->getStoreId(), $userModel->getId(), Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE);
				$rest->triggerCampaign( Techtwo_Mailplus_Helper_Rest::CAMPAIGN_NEWSLETTER, $userModel->getId() );
			}
			else
			{
				if ( $userModel->getId() )
					$rest->disableContact($userModel->getStoreId(), $userModel->getId() );
			}
				
		}
		catch (Exception $e)
		{
			throw $e;
		}

		return $this;
	}

	/**
	 * Handles customer data by observing the save event
	 *
	 * @param Varien_Event_Observer $observer
	 * @return mixed
	 * @throws Exception
	 */
	public function synchronize_customer(Varien_Event_Observer $observer)
	{
		if (Mage::registry('mailplusModifiedContacts')) {
			// Dont sync back to MailPlus when getting updates from MailPlus
			return;
		}
		
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');

		/* @var $customer Mage_Customer_Model_Customer */
		$customer = $observer->getEvent()->getData('customer');

		$store_id = $customer->getStore()->getId();

		/* @var $subscriber Mage_Newsletter_Model_Subscriber */
		$subscriber = Mage::getModel('newsletter/subscriber');
		$subscriber->loadByCustomer($customer);


		// Determine if this is a new account
		if ( strtolower($customer->getData('created_in')) == 'admin' )
		{
			$is_new   = $customer->getData('created_at') == $customer->getData('updated_at');
		}
		else
		{
			$is_new = 'account' == Mage::app()->getRequest()->getControllerName() && FALSE!==strpos(Mage::app()->getRequest()->getActionName(), 'create');
			if ( $is_new )
			{

				/*
				 workaround register issue whereas customer->save event is invoked twice
				When a new user creates an account, the createPostAction() is run when the form is submitted. This action invokes 'customer->save()'.

				THEN, after the customer has been created, setCustomerAsLoggedIn() is called by createPostAction(). This in turn calls setCustomer(), which has this little bit of code:
				http://stackoverflow.com/questions/5838346

				this is resolved in magento 1.5+, but in Magento 1.5 we still have this compelling issue.
				*/


				if ( $customer->getId() === Mage::registry('Techtwo_Mailplus_Observer_Customer->synchronize_customer') )
					return $this;
				Mage::register('Techtwo_Mailplus_Observer_Customer->synchronize_customer', $customer->getId());
			}
		}


		$current_is_subscribed = $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
		$change_subscription = $customer->hasData('is_subscribed');
		if ( $change_subscription )
		{
			if ( $current_is_subscribed && '1' == $customer->getData('is_subscribed') )
				$change_subscription = FALSE;
			elseif ( !$current_is_subscribed && '1' != $customer->getData('is_subscribed') )
			$change_subscription = FALSE;
		}

		$wants_newsletter = $change_subscription && (TRUE === $customer->getData('is_subscribed') || '1' == $customer->getData('is_subscribed'));

		// if this is a new use we do not want a newsletter ... we don't need to change anything
		if ( $is_new && $change_subscription && !$wants_newsletter )
			return $this;

		/* Insert new MailPlus User if required */
		/* @var $userModel Techtwo_Mailplus_Model_User */
		$userModel = Mage::getModel('mailplus/user');
		$userModel = $userModel->findByCustomer($customer);

		// not found by customer ... maybe by email ( in that case , the user was subscribed before he/she became registered themselves)
		if ( $userModel->getId() < 1 )
			$userModel = $userModel->findByEmail( $customer->getEmail(), $store_id );

		$is_new = $userModel->getId() < 1;

		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');

		// ALSO send a mail to this contact after he/she has registered
		// this also sends a welcome mail on reactivating the member
		try
		{
			if ( $is_new )
			{
				if ( $change_subscription && $wants_newsletter )
				{
					$userModel = $dataHelper->createUserFromCustomer($customer);
					$userModel->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE );

					$userModel->save();
					$rest->triggerCampaign( Techtwo_Mailplus_Helper_Rest::CAMPAIGN_NEWSLETTER, $userModel->getId() );
				}
				else
					$userModel = NULL;
			}
			else
			{
				$userModel->setData('customer_id', $customer->getId());
				$userModel->setData('firstname', $customer->getData('firstname'));
				$userModel->setData('lastname', $customer->getData('lastname'));
				$userModel->setData('email', $customer->getData('email'));

				if ( ! $change_subscription )
				{ // subscription is not changed but user changed data
					$userModel->save();

				}
				elseif ( $wants_newsletter )
				{
					$userModel->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE );
					$userModel->save();
					$rest->triggerCampaign( Techtwo_Mailplus_Helper_Rest::CAMPAIGN_NEWSLETTER, $userModel->getId() );
				}
				else
				{
					$userModel->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, FALSE );
					$userModel->save();
				}

			}
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			throw $e;
		}

		return $this;
	}


	public function delete_customer(Varien_Event_Observer $observer)
	{
		/* @var $customer Mage_Customer_Model_Customer */
		$customer = $observer->getEvent()->getData('customer');
		/* @var $user Techtwo_Mailplus_Model_User */
		$user = Mage::getModel('mailplus/user');
		$user = $user->loadByCustomer($customer);
		if ($user && $user->getId()) {
			$this->deleteMailplusUser($user->getStoreId(), $user->getId());
		}
		return $this;
	}

	public function delete_visitor(Varien_Event_Observer $observer)
	{
		/* @var $customer Mage_Newsletter_Model_Subscriber */
		$subscriber = $observer->getEvent()->getData('subscriber');
		$userModel = Mage::getModel('mailplus/user')->load( $subscriber->getData('subscriber_email'), 'email' );
		if ( $userModel && $userModel->getId() )
			$this->deleteMailplusUser($userModel->getStoreId(), $userModel->getId());
		return $this;
	}

	private function deleteMailplusUser($storeId,  $externalId)
	{
		$rest = Mage::helper('mailplus/rest');
		$rest->disableContact($storeId, $externalId);
	}

	/**
	 * Registers conversion on a parameter in the request url
	 *
	 * MailPlus newsletter example url
	 * http://magento.local/kitty-hats.html?utm_medium=email&utm_campaign=Nieuwsbrief&utm_content=1-8&utm_term=Kitty+hats&utm_source=Nieuwsbrief&utm_name=Kitty+hats&mpid=DBPgSjYfdL6maCi-Dcw7Fr6anKWdvvn-myMih3JhFztyVvk
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function register_conversion( Varien_Event_Observer $observer )
	{			
		$front = $observer->getFront();
		if ($front) {
			$request = $front->getRequest();
			$mailplus_click = $request->getParam('mpid');
		} else {
			/** @var $controller Mage_Core_Controller_Front_Action */
			$controller = $observer->getControllerAction();
			if ($controller) {
				$mailplus_click = $controller->getRequest()->getQuery('mpid');
			}	
		}

		if ($mailplus_click) {
			/* @var $session Mage_Core_Model_Session */
			$session = Mage::getSingleton('core/session');
			$data = array( Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART => array('mpid'=>$mailplus_click, 'is_converted'=>FALSE) );			
			if ( $session->hasData(self::SESSION_CONVERSION_KEY) ) {
				$data = array_unique(array_merge($session->getData(self::SESSION_CONVERSION_KEY), $data));
			}
	
			$session->setData(self::SESSION_CONVERSION_KEY, $data);
		}
	}

	public function register_conversion_mailplus( Varien_Event_Observer $observer )
	{
		/* @var $session Mage_Core_Model_Session */
		$session = Mage::getSingleton('core/session');

		$ses  = $session->hasData(self::SESSION_CONVERSION_KEY)? $session->getData(self::SESSION_CONVERSION_KEY):array();

		if ( array_key_exists(Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART, $ses ) )
		{
			$mailplus_click = $ses[Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART]['mpid'];

			if ( FALSE === $ses[Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART]['is_converted'] )
			{
				if ( NULL !== $mailplus_click )
				{
					/* @var $rest Techtwo_Mailplus_Helper_Rest */
					$rest = Mage::helper('mailplus/rest');
					$rest->registerConversion(Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART, $mailplus_click);
				}
				$ses[Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART]['is_converted'] = TRUE;

				$session->setData(self::SESSION_CONVERSION_KEY, $ses);
			}
		}
	}

	/**
	 * Create the actual conversion
	 * Triggers conversion if there is one in the session.
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */
	public function create_conversion( Varien_Event_Observer $observer )
	{
		/* @var $session Mage_Core_Model_Session */
		$session = Mage::getSingleton('core/session');
		$ses  = $session->hasData(self::SESSION_CONVERSION_KEY)? $session->getData(self::SESSION_CONVERSION_KEY):array();

		if ( ! array_key_exists(Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART, $ses ) )
			return TRUE;

		$mailplus_click = $ses[Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART]['mpid'];

		if (NULL !== $mailplus_click) {
			/* @var $mailplus_product Techtwo_Mailplus_Model_Product */
			$mailplus_product = Mage::getModel('mailplus/product');

			/** @var $order Mage_Sales_Model_Order */
			$order = $observer->getOrder();

			$product_ids = array();
			foreach ( $order->getAllVisibleItems() as $item ) {
				/* @var $item Mage_Sales_Model_Order_Item */
				if ($item && $item->getProductId()) {
					$product = $mailplus_product->findByProductId($item->getProductId());
					if ( $product && $product->getId() )
						$product_ids []= $product->getExternalId();
				}
			}

			if (count($product_ids)) {
				/* @var $rest Techtwo_Mailplus_Helper_Rest */
				$rest = Mage::helper('mailplus/rest');
	
				return $rest->convertConversion( Techtwo_Mailplus_Helper_Rest::CONVERSION_SHOPPINGCART, $mailplus_click, array(
						'products'         => $product_ids,
						'value'            => round($order->getGrandTotal()*100) // MailPlus wants cents
				) );
			}
		}
	}

	private function orderToProductList($order) {
		$productIds = array();
		$items = $order->getAllVisibleItems();

		foreach ($items as $item) {
			$mailplus_product = Mage::getModel('mailplus/product');
			$mailplus_product = $mailplus_product->findByProductId($item->getProductId(), $item->getStoreId());

			$productIds[] = $mailplus_product->getExternalId();
		}

		return $productIds;
	}

	/**
	 * @param Varien_Event_Observer $observer
	 */
	public function apply_sales_order_campaign( Varien_Event_Observer $observer ) {
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');
		/* @var $configHelper Techtwo_Mailplus_Helper_Config */
		$configHelper = Mage::helper('mailplus/config');

		/* @var $order Mage_Sales_Model_Order */
		$order = $observer->getOrder();
		
		if (!$configHelper->contactSyncAllowedForStore($order->getStoreId())) {
			return;
		}
		
		$originalState = $order->getOrigData('state');

		if ( $order->getState() !== $originalState) {
			// Delete the order from MailPlus when it is canceled
			if ($order->getState() === Mage_Sales_Model_Order::STATE_CLOSED) {
				$rest->deleteOrder($order);

			} else if ($order->getState() === Mage_Sales_Model_Order::STATE_COMPLETE) {
				// On order change to 'complete' which happens on button 'ship'
				/* @var $user Techtwo_Mailplus_Model_User */
				$user = $rest->getUserFromOrder($order);
				if ($user) {
					try {
						$user->save();
						$rest->saveOrder($order, TRUE);
						$this->triggerReviewCampaign($user, $order);
					}
					catch (Exception $e) {
						Mage::logException($e);
					}
				}
			}
		} else if ($order->getState() === Mage_Sales_Model_Order::STATE_COMPLETE) {
			try {			
				$rest->saveOrder($order, TRUE);
			}
			catch (Exception $e) {
				Mage::logException($e);
			}
		}
	}

	private function triggerReviewCampaign($user, $order) {
		$extraData = array();
		$productList = $this->orderToProductList($order);

		$extraData['campaignFields'] = array(
				array(
						'values' => $productList,
						'name' => 'productlist',
						'type' => 'PRODUCTLIST'
				)
		);

		$rest = Mage::helper('mailplus/rest');

		try {
			$rest->triggerCampaign( Techtwo_Mailplus_Helper_Rest::CAMPAIGN_PRODUCT_REVIEW, $user->getId(), $extraData );
		}
		catch (Exception $e) {
			Mage::logException($e);
		}
	}

}
