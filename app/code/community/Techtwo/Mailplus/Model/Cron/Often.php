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
class Techtwo_Mailplus_Model_Cron_Often
{
	protected static $_ran_cron;

	public function run()
	{
		if ( self::$_ran_cron ) {
			return;
		}
		self::$_ran_cron = TRUE;
	
		$currentTime = Mage::getModel('core/date')->timestamp(time());

		Mage::getModel('mailplus/info')->loadByName(Techtwo_Mailplus_Helper_Cron::OFTEN_START)
			->setName(Techtwo_Mailplus_Helper_Cron::OFTEN_START)
			->setValue($currentTime)
			->save();
		
		$this->_abandonedCartCampaign();	
		$continue = true;

		if ($continue) {
			$continue = $this->_processRestqueue();			
		}
		if ($continue) {
			$continue = $this->_processScheduledSynchronizeItems();
		}

		$currentTime = Mage::getModel('core/date')->timestamp(time());
		Mage::getModel('mailplus/info')->loadByName(Techtwo_Mailplus_Helper_Cron::OFTEN_END)
			->setName(Techtwo_Mailplus_Helper_Cron::OFTEN_END)
			->setValue($currentTime)
			->save();
	
	}

	
	/**
	 * Process the restqueue. Returns true when the queue is empty or false
	 * when there are more items to be processed
	 * 
	 * @return boolean
	 */
	protected function _processRestqueue() {
		$rest = Mage::helper('mailplus/rest');
		
		$pageSize = 100;
		$items = Mage::getModel('mailplus/restqueue')
			->getCollection()
			->setOrder('next_run_at', 'ASC')
			->addFieldToFilter('next_run_at', array('lteq' => Mage::getModel('core/date')->gmtDate()))
			->setPageSize($pageSize);
		
		$size = $items->getSize();
		
		$numPages = ceil($size / $pageSize);

		if ($size > 0 ) {
			for ($page = 1; $page <= $numPages; $page++) {
				$items->setCurPage($page);
				
				foreach ($items as $item) {
					$rest->handleQueueItem($item);
				}
				
				$items->clear();
			}
		}
		
		return ($size == 0);
	}
	
	
	public function testAbandonedCartCampaign()
	{
		$this->_abandonedCartCampaign();
	}

    public function testProcessScheduledSynchronizeProducts()
    {
        $this->_processScheduledSynchronizeProducts();
    }

	/**
	 * Finds lost carts of the previous days and sends a lost cart campaign whenever required
	 * 
	 * TODO: Split this code up to make it more readable
	 */
	protected function _abandonedCartCampaign()
	{
		/* @var $quoteCollection Mage_Sales_Model_Mysql4_Quote_Collection */
		$quoteCollection = Mage::getModel('sales/quote')->getCollection();
		$sales_table = $quoteCollection->getTable('sales/order');
		$abandoned_campaign_table = $quoteCollection->getTable('mailplus/abandoned_campaign');

		$baseTime = strtotime('-4 hours', time());
		$date = date('Y-m-d H:i:s', $baseTime);
		$yesYesterday = date('Y-m-d 00:00:00', strtotime('-7 days', $baseTime));

		// send quotes only if they were filled with products
		$quoteCollection->addFieldToFilter('items_count', array('gt' => 0));

		// we want to fetch lost quotes from yesterday and the $yesYesterday
		$quoteCollection->addFieldToFilter('updated_at', array('gt' => $yesYesterday));
		$quoteCollection->addFieldToFilter('updated_at', array('lt' => $date));

		// We don't want converted quotes and we don't want quotes we already send
		$quoteCollection->addFieldToFilter('entity_id', array( 'nin' =>  new Zend_Db_Expr("SELECT `quote_id` FROM `{$sales_table}`")));
		$quoteCollection->addFieldToFilter('entity_id', array( 'nin' =>  new Zend_Db_Expr("SELECT `quote_id` FROM `{$abandoned_campaign_table}`")));

		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');

		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');


		$debug = 'test' === Mage::app()->getFrontController()->getRequest()->getControllerName();
		if ( $debug )
			echo "Abandoned cart campaign started for possible {$quoteCollection->count()}<br />\r\n";

		
		/* @var $appEmulation Mage_Core_Model_App_Emulation */
		$appEmulation = Mage::getSingleton('core/app_emulation');
		
		foreach ( $quoteCollection as $quote )
		{
			/* @var $quote Mage_Sales_Model_Quote */
			$customerId = $quote->getCustomerId()? (int) $quote->getCustomerId():NULL;
			if ( !$customerId )
				continue;

			$storeId = $quote->getStoreId();
			
			/* @var $customer Mage_Customer_Model_Customer */
			$customer = Mage::getModel('customer/customer');
			$customer->load( $customerId );
			
			$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
			
			$items = $quote->getAllVisibleItems();
			$productList = array();
			foreach ($items as $item) {
				/* @var $mailplus_product Techtwo_Mailplus_Model_Product */
				$mailplus_product = Mage::getModel('mailplus/product');
				$mailplus_product = $mailplus_product->findByProductId( $item->getProductId(), $item->getStoreId() );

				if ( $mailplus_product->getId() ) {
					$productList []= $mailplus_product->getExternalId();
				}
			}

			$extraData = array();
			
			$link = Mage::getUrl('mailplus/use/cart', array(
					'id' => $quote->getId(),
					'_store' => $storeId,
					'_store_to_url' => true
			));

			if ($productList)
			{
				$extraData['campaignFields'] = array(
					array(
						'name'  => 'linktocart',
						'value' => $link
					),
					array(
						'values' => $productList,
						'name' => 'productlist',
						'type' => 'PRODUCTLIST'
					)
				);
			}

			/* @var $user Techtwo_Mailplus_Model_User */
			$user = Mage::getModel('mailplus/user');
			$user->loadByCustomer( $customer );

			// If we have no mailplus/user , we make one
			if (!$user->getId() && $user->getId()<1 )
			{
				/* @var $user Techtwo_Mailplus_Model_User */
				$user = $dataHelper->createUserFromCustomer($customer);
				try {
					$user->save();
				}
				catch( Exception $e ) {
					Mage::logException($e);
				}
			}

			// Okay, we should now have a mailplus user
			if ($user->getId() && $user->getId()>0 )
			{
				$rest->triggerCampaign( Techtwo_Mailplus_Helper_Rest::CAMPAIGN_ABANDONED_CART, $user->getId(), $extraData );
				// Now ensure we don't keep sending the campaign
				// so we just create a record in the abandoned_campaign table
				/* @var $campaign Techtwo_Mailplus_Model_Abandoned_Campaign */
				$campaign = Mage::getModel('mailplus/abandoned_campaign');
				$campaign->setId( $quote->getId() );
				$campaign->setCreatedAt(date('Y-m-d H:i:s'));
				try { $campaign->save(); }
				catch( Exception $e ) { Mage::logException($e); }
			}

			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
		}
	}

    /**
     * Schedules all items to be synced to MailPlus
     */
    protected function _processScheduledSynchronizeItems() {
    	$mailplusHelper = Mage::helper('mailplus');
    	
    	$typeOrder = Techtwo_Mailplus_Model_Syncqueue::getTypesInOrder();
    	$type = reset($typeOrder);

    	$itemsToSync = 200;
    	$itemsSynced = 0;
    	
    	while ($itemsSynced < 200 && $type !== false) {
	    	$collection = Mage::getModel('mailplus/syncqueue')->getCollection()
	    		->setPageSize($itemsToSync)
	    		->addFieldToFilter('synctype', array("eq" => $type))
	    		->setOrder('created_at', 'ASC');
    		
	    	$size = $collection->getSize();
	    	
	    	if ($size > 0) {
		    	$collection->setCurPage(1);
		    	foreach($collection as $item) {
		    		try {
		    			$this->syncSyncItem($item);
		    		}
		    		catch( Exception $ex) {
		    			Mage::logException($ex);
		    		}
		    		$item->delete();
		    		$itemsSynced++;
		    	}
		    	$collection->clear();
	    	} else {
	    		$type = next($typeOrder);
	    	}
    	}
    }
    
    /**
     * Syncs the item (customer, order, product or subscriber) to MailPlus
     * 
     * @param Techtwo_Mailplus_Model_Syncqueue $item
     */
    public function syncSyncItem($item) {
    	/** @var $product Techtwo_Mailplus_Helper_Data */
    	$mailplusHelper = Mage::helper('mailplus');
    	$rest = Mage::helper('mailplus/rest');
    	
    	switch ($item->getSynctype()) {
    		case Techtwo_Mailplus_Model_Syncqueue::TYPE_CUSTOMER:
	     		/** @var $product Mage_Catalog_Model_Product */
	    		$customer = Mage::getModel('customer/customer')->load($item->getSyncid());
	
	    		if ($customer && $customer->getId() && $customer->getIsActive()) {
	    			$userModel = Mage::getModel('mailplus/user');
	    			$userModel->loadByCustomer($customer);
	
	    			if (!$userModel->getId()) {
						$userModel = $mailplusHelper->createUserFromCustomer($customer);				
	    			} else {
		    			// Update the data
	    				$userModel->setData('firstname', $customer->getData('firstname'));
		    			$userModel->setData('lastname', $customer->getData('lastname'));
		    			$userModel->setData('email', $customer->getData('email'));
		    			// Do not set the permissions when the contact is already synced to MailPlus
	    			}
	    			$userModel->save(); // also triggers the rest call to save the customer to MailPlus
	    		}
	    		break;
    		case Techtwo_Mailplus_Model_Syncqueue::TYPE_ORDER:
    			$order = Mage::getModel('sales/order')->load($item->getSyncid());
				if ($order && $order->getId()) {
    				$rest->saveOrder($order, FALSE);
				}
    			break;
    		case Techtwo_Mailplus_Model_Syncqueue::TYPE_PRODUCT:
    			$product = Mage::getModel('catalog/product')->load($item->getSyncid());
    			if ($product && $product->getId()) {
    				$rest->saveProduct($product);
    			}
    			break;
    		case Techtwo_Mailplus_Model_Syncqueue::TYPE_SUBSCRIBER:
    			/* @var $subscriberCollection Mage_Newsletter_Model_Mysql4_Subscriber_Collection */
    			$subscriber = Mage::getModel('newsletter/subscriber')->load($item->getSyncid());
    			/* @var $mailplus_user Techtwo_Mailplus_Model_User */
				$mailplus_user = Mage::getModel('mailplus/user');
				
				if ($subscriber->getCustomerId()) {
					/* @var $customer Mage_Customer_Model_Customer */
					$customer = Mage::getModel('customer/customer')->load($subscriber->getCustomerId());
	    			if ( $customer && $customer->getId() ) {
	    				$mailplus_user->loadByCustomer($customer);
	    				if (!$mailplus_user || !$mailplus_user->getUserId()) {
	    					/* Set the values from the customer. This will also set the newsletter permission
	    					   if needed */
	    					$mailplus_user = $mailplusHelper->createUserFromCustomer($customer);
	    					$mailplus_user->save();
	    					return;
	    				} 
	    			} 
				}

				// Only set the values when no existing mailplus_user was found.
				if (!$mailplus_user || !$mailplus_user->getUserId()) {
					$mailplus_user = $mailplus_user->findByEmail($subscriber->getEmail());
				}
				
    			// Only set the values when no existing mailplus_user was found.
    			if (!$mailplus_user || !$mailplus_user->getUserId()) {
	    			$mailplus_user = Mage::getModel('mailplus/user');
	    			$mailplus_user->setEmail( $subscriber->getEmail() );
	    			$mailplus_user->setFirstname( '' );
	    			$mailplus_user->setLastname( '' );
	    			$mailplus_user->setCustomerId(NULL);
	    			$mailplus_user->setStoreId( $subscriber->getStoreId() );
	    			$mailplus_user->setIsTest(FALSE);
	    			$mailplus_user->setCreatets(time());
	    			
    				if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
	    				$mailplus_user->setEnabled(true);
	    				$mailplus_user->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE );
	    			} else {
	    				$mailplus_user->setEnabled(false);
	    			}
	    			
    			}  else {
    				// user found in mailplus table. Check if the user is in MailPlus
    				$contact = $rest->getContactByExternalId($mailplus_user->getId(), $mailplus_user->getStoreId());
    				
    				if (!$contact) {
						// only update permissions when the user is not in MailPlus
    					if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
    						$mailplus_user->setEnabled(true);
    						$mailplus_user->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE );
    					} else {
    						$mailplus_user->setEnabled(false);
    					} 					
    				} 
    			}

    			$mailplus_user->save();
    	}
    }
}
	    			