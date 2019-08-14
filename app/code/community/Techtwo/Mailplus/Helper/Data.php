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
class Techtwo_Mailplus_Helper_Data extends Mage_Core_Helper_Abstract
{
	const PRODUCT_TYPE_ECOM     = 'ecomm';
	const PRODUCT_TYPE_ECOM_PRO = 'ecomm_pro';
	const PRODUCT_TYPE_ECOM_ENTERPRISE = 'ecomm_enterprise';

	const MAPPING_ACCOUNT = 'account';
	const MAPPING_ADDRESS = 'address';

	const ORDERS_CACHE_SYNCHRONIZE_KEY = 'techtwo_mailplus_model_orders-synchronize';
	const USERS_CACHE_SYNCHRONIZE_KEY = 'techtwo_mailplus_model_user-synchronize';
	const PRODUCTS_CACHE_SYNCHRONIZE_KEY = 'techtwo_mailplus_model_product-synchronize';

	const SMALL_IMAGE_MAX = 145;
	const LARGE_IMAGE_MAX = 185;
	
	const NL_ZIPCODE_REGEXP = '/^[1-9][0-9]{3}[\s]?[A-Za-z]{2}$/i';
	const IS_MULTILINE_REGEXP = '/^(.+)-line-(\d+)$/i';
	
	public function getCacheFile($cacheType) {
		$file = Mage::getBaseDir('var').DIRECTORY_SEPARATOR.'cache'.DS.$cacheType.'.cache';
		return $file;
	}
	

	/**
	 * Check if module is active
	 * @return bool
	 */
	public function isEnabled()
	{
		return TRUE === Mage::getConfig()->getModuleConfig('Techtwo_Mailplus')->is('active', 'true') && '1' === Mage::getStoreConfig('mailplus/general/active');
	}

	public function isSynchronizeContactsAllowed( $store_id=NULL )
	{
		return 'all' === Mage::getStoreConfig('mailplus/general/synchronize', $store_id);
	}

	public function isSynchronizeProductsAllowed( $store_id=NULL )
	{
		return TRUE; // you can't turn it off yet
	}
	
	/**
	 * This assumes that the foreign key is the entity id of the eav table.
	 * $collection is a collection object of a flat table.
	 * $mainTableForeignKey is the name of the foreign key to the eav table.
	 * $eavType is the type of entity (the entity_type_code in eav_entity_type)
	 * 
	 * @url http://codemagento.com/2011/03/joining-an-eav-table-to-flat-table/
	 */
	public function joinEavTablesIntoCollection($collection, $mainTableForeignKey, $eavType)
	{
		$entityType = Mage::getModel('eav/entity_type')->loadByCode($eavType);
		$attributes = $entityType->getAttributeCollection();
		$entityTable = $collection->getTable($entityType->getEntityTable());

		//Use an incremented index to make sure all of the aliases for the eav attribute tables are unique.
		$index = 1;
		foreach ($attributes->getItems() as $attribute)
		{
			$alias = 'table' . $index;
			if ($attribute->getBackendType() != 'static')
			{
				$table = $entityTable . '_' . $attribute->getBackendType();
				$field = $alias . '.value';
				$collection->getSelect()
					->joinLeft(
						array($alias => $table),
						'main_table.' . $mainTableForeignKey . ' = ' . $alias . '.entity_id and ' . $alias . '.attribute_id = ' . $attribute->getAttributeId(),
						array($attribute->getAttributeCode() => $field)
					);
			}
			$index++;
		}
		//Join in all of the static attributes by joining the base entity table.
		$collection->getSelect()->joinLeft($entityTable, 'main_table.' . $mainTableForeignKey . ' = ' . $entityTable . '.entity_id');

		return $collection;
	}
	
	protected $_genderOptions;

	public function getMagentoGenderMaleId( )
	{
		$this->_genderHelper();
		return $this->_genderOptions['male'];
	}

	public function getMagentoGenderFemaleId( )
	{
		$this->_genderHelper();
		return $this->_genderOptions['female'];
	}

	protected function _genderHelper()
	{
		if ( NULL === $this->_genderOptions )
		{
			$entityType = Mage::getModel('eav/config')->getEntityType('customer');
			$entityTypeId = $entityType->getEntityTypeId();

			/* @var $attribute Mage_Eav_Model_Entity_Attribute */
			$attribute = Mage::getResourceModel('eav/entity_attribute_collection')
				->setCodeFilter('gender')
				->setEntityTypeFilter($entityTypeId)
				->getFirstItem();


			//print_r($attribute->debug());

			if ( $attribute->usesSource() )
			{
				$options = $attribute->getSource()->getAllOptions(false);
			}

			$option = array_shift($options);
			$this->_genderOptions = array();
			$this->_genderOptions['male'] = $option? $option['value']:NULL;
			$option = array_shift($options);
			$this->_genderOptions['female'] = $option? $option['value']:NULL;
		}
	}

	public function getCustomerTotalOrders( $customer_id )
	{
		$collection = Mage::getModel('sales/order')->getCollection();
		/* @var $collection Mage_Sales_Model_Entity_Order_Collection */
		$collection->addFieldToFilter('customer_id', $customer_id);

		return $collection->count();
	}

	public function getCustomerFirstPurchaseDate($customer_id)
	{
		$collection = Mage::getModel('sales/order')->getCollection();
		/* @var $collection Mage_Sales_Model_Entity_Order_Collection */
		$collection
			->addFieldToFilter('customer_id', $customer_id)
		//->addFieldToFilter('state', 'complete')
			->setOrder('created_at', 'asc')
			->setPageSize(1)
		;

		if ( $collection->count() > 0 )
		{
			/* @var $order Mage_Sales_Model_Order */
			$order = $collection->getFirstItem();
			return $order->getDataUsingMethod('created_at');
		}
		return NULL;
	}

	public function getCustomerLastPurchaseDate($customer_id)
	{
		$collection = Mage::getModel('sales/order')->getCollection();
		/* @var $collection Mage_Sales_Model_Entity_Order_Collection */
		$collection
			->addFieldToFilter('customer_id', $customer_id)
		//->addFieldToFilter('state', 'complete')
			->setOrder('created_at', 'desc')
			->setPageSize(1)
		;

		if ( $collection->count() > 0 )
		{
			/* @var $order Mage_Sales_Model_Order */
			$order = $collection->getFirstItem();
			return $order->getDataUsingMethod('created_at');
		}
		return NULL;
	}

	/*
	 * Returns the value which can be send to MailPlus. Returns NULL when nothing needs to be set.
	 */
	public function attributeToMailplusValue($elem, $attribute, $mapping) {
		$attributeCode = $attribute->getAttributeCode();
		switch ($attributeCode) {
			case 'gender':
				$gender = $elem->getDataUsingMethod( $attributeCode );
				
				if ( $this->getMagentoGenderMaleId() == $gender )
					return 'M';
				elseif ( $this->getMagentoGenderFemaleId() == $gender )
					return 'F';
				break;
			case 'dob':
				$dob = $elem->getDataUsingMethod( $attributeCode );
				if (!$dob )	{
					return NULL;
				}

				return date('Y-m-d', strtotime($dob));
			case 'postcode':
				// Make sure to send only NL formatted postcodes when postcode is mapped to MailPlus postalcode.
				if (isset($mapping['postcode']) && $mapping['postcode'] === 'postalCode') {
					$value = (string) $elem->getDataUsingMethod( $attributeCode );
					if (preg_match(Techtwo_Mailplus_Helper_Data::NL_ZIPCODE_REGEXP, $value)) {
						return $value;
					}
				} else {
					return (string) $elem->getDataUsingMethod( $attributeCode );
				}
				break;
			case 'storedescription':
				$store = null;
				if ($elem->getStore){
					$store = $elem->getStore();
				} else {
					$store = Mage::app()->getStore($elem->getStoreId());
				}
				return $store->getWebsite()->getName() . ' - ' . $store->getGroup()->getName() . ' - ' . $store->getName();
			case 'firstpurchasedate':
				$date = $this->getCustomerFirstPurchaseDate( $elem->getId() );
				if ($date) {
					return date(DateTime::ATOM, strtotime($date));
				}
				break;
			case 'lastpurchasedate':
				$date = $this->getCustomerLastPurchaseDate( $elem->getId() );
				if ($date) {
					return date(DateTime::ATOM, strtotime($date));
				}
				break;	
			case 'group_id': {
				$groupId = $elem->getDataUsingMethod( $attributeCode );
				return Mage::getModel('customer/group')->load($groupId)->getCode();
			}			
			default: 
				return (string) $elem->getDataUsingMethod( $attributeCode );
		};
				
		return NULL;
	}
	
	public function addAttributeToProperties($properties, $attribute, $customer, $mapping) {
		if ($attribute->getFrontendInput() === 'multiline') {
			$data = $customer->getDataUsingMethod( $attribute->getAttributeCode() );

			for ($i = 0; $i < $attribute->getMultilineCount(); $i++) {			
				$mappingId = $attribute->getAttributeCode() . '-line-' . ($i +1);
				if (isset($mapping[$mappingId]) && isset($data[$i])) {
					$properties[$mapping[$mappingId]] = $data[$i];
				}
			}
		} else {
			if (isset($mapping[$attribute->getAttributeCode()])) {
				$mailplusProperty = $mapping[$attribute->getAttributeCode()];
				$value = $this->attributeToMailplusValue($customer, $attribute, $mapping);
				if ( $value != NULL ) {
					
					$properties[$mailplusProperty] = $value;
				}
			}
		}
		
		return $properties;
	}

	
	public function getSyncCount($websiteId, $type = null) {
		if (!$type)
			return Mage::getModel('mailplus/syncqueue')->getCollection()->getSize();
		
		return Mage::getModel('mailplus/syncqueue')->getCollection()
				->addFieldToFilter('websiteid', Array('eq' => $websiteId))
				->addFieldToFilter('synctype', Array('eq' => $type))
				->getSize();
	}

	public function saveSyncItem($websiteId, $id, $type) {
		$syncItem = Mage::getModel('mailplus/syncqueue');
		$syncItem->setSynctype($type);
		$syncItem->setWebsiteid($websiteId);
		$syncItem->setSyncid($id);
		$syncItem->setCreatedAt(date('Y-m-d H:i:s'));
		$syncItem->save();
	}
	
	
	public function getRestqueueCount() {
		return Mage::getModel('mailplus/restqueue')->getCollection()->getSize();
	}
	
	public function getAllAttributeGroupNames() {
		$groups = Mage::getModel('eav/entity_attribute_group')
		    ->getCollection()
		    ->load();

		$uniqGroups = Array();
		$return = Array();

		foreach($groups as $group) {
			$uniqGroups[$group->getAttributeGroupName()] = true;
		}
		foreach($uniqGroups as $name => $blup) {
			$return[] = $name;
		}

		return $return;
	}

	/**
	 * Create a mailplus/user from a customer. Only call this when the customer
	 * has no corresponding mailplus/user. This will also check the 
	 * newsletter/subscriber if the customer has the newsletters permission
	 * 
	 * @param  Mage_Customer_Model_Customer $customer
	 * @return Techtwo_Mailplus_Model_User
	 */
	public function createUserFromCustomer($customer) {
		 /* @var $user Techtwo_Mailplus_Model_User */
		$user = Mage::getModel('mailplus/user');
		
		$existingUser = $user->findByEmail($customer->getEmail());
		if (!$existingUser->getId()) {
			// only check and set the permission when no existing user is found
			$subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
			if ($subscriber && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
				$user->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE );
			}
			
			$user->setEmail( $customer->getEmail() );
			$user->setCreatets( time() );
		} else {
			$user = $existingUser;
		}
		
		if ($customer->getFirstname() && trim($customer->getFirstname()) != '') {
			$user->setFirstname( $customer->getFirstname());
		}
		if ($customer->getLastname() && trim($customer->getLastname()) != '') {
			$user->setLastname( $customer->getLastname());
		}
		$user->setCustomerId( $customer->getId());
		$user->setStoreId( $customer->getStore()->getId() );
		$user->setEnabled(TRUE);
				
		return $user;
	}
	
	/**
	 * Returns the website from the request, or the first website when there is no website parameter
	 * 
	 * @return website
	 */
	public function getWebsiteFromRequest() {
		$websiteCode = Mage::app()->getFrontController()->getRequest()->getParam('website');
		$website = null;
		if (!$websiteCode) {
			$sites = Mage::app()->getWebsites();
			$website = reset($sites);
		} else {
			$website = Mage::getModel('core/website')->load($websiteCode);
		}
		return $website;
	}
}
