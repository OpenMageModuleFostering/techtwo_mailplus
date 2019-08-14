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
class Techtwo_Mailplus_Mailplus_Adminhtml_StatusController extends Mage_Adminhtml_Controller_Action{

	public function indexAction()
	{
		$this->loadLayout()->_setActiveMenu('mailplus');
		$this->_addLeft($this->getLayout()->createBlock('adminhtml/template')->setTemplate('mailplus/website_switcher.phtml'));
		
		$this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('mailplus/status.phtml'));
		$this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('mailplus/cronstatus.phtml'));
		$this->_addContent($this->getLayout()->createBlock('mailplus/adminhtml_syncstatus'));
		
		$this->renderLayout();
	}

	public function syncAction() {
		$synctype = Mage::app()->getFrontController()->getRequest()->getParam('synctype');
		$mailplusHelper = Mage::helper('mailplus');
		$website = $mailplusHelper->getWebsiteFromRequest();
		
		$websiteId = $website->getId();

		$mailplus = Mage::helper('mailplus');
		
		switch ($synctype) {
			case Techtwo_Mailplus_Model_Syncqueue::TYPE_CUSTOMER:
				$this->_fillCustomerSyncCache($websiteId);
				break;
			case Techtwo_Mailplus_Model_Syncqueue::TYPE_ORDER:
				$this->_fillOrderSyncCache($websiteId);
				break;
			case Techtwo_Mailplus_Model_Syncqueue::TYPE_PRODUCT:
				$this->_fillProductSyncCache($websiteId);
				break;
			case Techtwo_Mailplus_Model_Syncqueue::TYPE_SUBSCRIBER:
				$this->_fillSubscriberSyncCache($websiteId);
				break;
		}
		
		$session = Mage::getSingleton('core/session');
		$session->addSuccess($mailplus->__('Sync for ' . $mailplus->__($synctype) . $mailplus->__(' started')));
		$this->_redirect('*/*', array('website' => $website->getCode()));
	}
	
	private function _fillOrderSyncCache($websiteId) {
		$configHelper = Mage::helper('mailplus/config');
		$mailplusHelper = Mage::helper('mailplus');
		$storeViewsToSync = array();
		
		$website = Mage::app()->getWebsite($websiteId);
		if ($configHelper->contactSyncAllowedForSite($websiteId)) {
			foreach($website->getGroups() as $group) {
				$stores = $group->getStores();
				
				foreach($stores as $store) {
					if ($configHelper->syncActiveForStore($store->getId())) {
						$storeViewsToSync[] = $store->getId();
					}
				}
			}
		}

		foreach ($storeViewsToSync as $storeId) {
			$orders = Mage::getModel('sales/order')->getCollection()
				->addAttributeToFilter('store_id', $storeId)
				->addAttributeToFilter('state', Mage_Sales_Model_Order::STATE_COMPLETE);
			
			$this->fillSyncCache($orders, $websiteId, Techtwo_Mailplus_Model_Syncqueue::TYPE_ORDER);
		}
	}

	private function fillSyncCache($collection, $websiteId, $type) {
		$dataHelper = Mage::helper('mailplus');
		$allIds = $collection->getAllIds();
		foreach($allIds as $id) {
			$dataHelper->saveSyncItem($websiteId, $id, $type);
		}
	}
	
	private function _fillCustomerSyncCache($websiteId) {
		$configHelper = Mage::helper('mailplus/config');
				
		if (!$configHelper->syncActiveForSite($websiteId) ||
					!$configHelper->contactSyncAllowedForSite($websiteId) ) {
			return;
		}

		$customers = Mage::getModel('customer/customer')->getCollection()
				->addAttributeToFilter('website_id', $websiteId);

		$this->fillSyncCache($customers, $websiteId, Techtwo_Mailplus_Model_Syncqueue::TYPE_CUSTOMER);			
	}

	private function _fillProductSyncCache($websiteId) {
		$configHelper = Mage::helper('mailplus/config');
	
		if (!$configHelper->syncActiveForSite($websiteId)) {
			return;
		}
	
		$products = Mage::getModel('catalog/product')->getCollection()
			->addWebsiteFilter($websiteId);
	
		$this->fillSyncCache($products, $websiteId, Techtwo_Mailplus_Model_Syncqueue::TYPE_PRODUCT);
	}
	
	public function synchronizeAction() {
		ignore_user_abort( true );
		/* @var $session Mage_Core_Model_Session */
		$session = Mage::getSingleton('core/session');
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');

		$website = $dataHelper->getWebsiteFromRequest();
		
		$synchronize = FALSE;

		$this->_fillSubscriberSyncCache($website->getId());
		$this->_fillCustomerSyncCache($website->getId());
		$this->_fillProductSyncCache($website->getId());
		$this->_fillOrderSyncCache($website->getId());	
		
		$session->addSuccess('MailPlus synchronizing is now scheduled');
		$this->_redirect('*/*', array('website' => $website->getCode()));
	}

	protected function _fillSubscriberSyncCache($websiteId ) {
		/* @var $configHelper Techtwo_Mailplus_Helper_Config */
		$configHelper = Mage::helper('mailplus/config');
		if (!$configHelper->contactSyncAllowedForSite($websiteId) ) {
			return;
		}
		
		$website = Mage::app()->getWebsite($websiteId);
		$storeIds = array();
		foreach($website->getGroups() as $group) {
			$stores = $group->getStores();
			foreach($stores as $store) {
				if ($configHelper->syncActiveForStore($store->getId())) {
					$storeIds[] = $store->getId();
				}
			}
		}
		$this->_redirect('*/website/' . $website->getCode());
		if (count($storeIds) > 0) {
			$subscriberCollection = Mage::getModel('newsletter/subscriber')->getCollection()
				->addFieldToFilter('store_id', array('in' => $storeIds));
			
			$this->fillSyncCache($subscriberCollection, $websiteId, Techtwo_Mailplus_Model_Syncqueue::TYPE_SUBSCRIBER);
		}
	}
}
