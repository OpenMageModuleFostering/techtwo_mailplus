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
/**
 * @method string getFirstname()
 * @method Techtwo_Mailplus_Model_User  setFirstname( $value )
 * @method string getLastname()
 * @method Techtwo_Mailplus_Model_User  setLastname( $value )
 * @method string getEmail()
 * @method Techtwo_Mailplus_Model_User  setEmail( $value )
 * @method string getCustomerId()
 * @method Techtwo_Mailplus_Model_User  setCustomerId( $value )
 * @method string getEnabled()
 * @method Techtwo_Mailplus_Model_User  setEnabled( $value )
 * @method string getStoreId()
 * @method Techtwo_Mailplus_Model_User  setCreatets($value)
 * @method string getCreatets()
 * @method Techtwo_Mailplus_Model_User setStoreId( $value )
 * @method Techtwo_Mailplus_Model_Mysql4_User_Collection getCollection()
 * @method Techtwo_Mailplus_Model_Mysql4_User _getResource()
 */
class Techtwo_Mailplus_Model_User extends Mage_Core_Model_Abstract 
{
	/**
	* The cached customer instance
	* @var Mage_Customer_Model_Customer
	*/
	protected $_customer;

	/**
	 * A flag determines if the customer lookup was done.
	 * Helps to identify if the cache took place
	 */
	protected $_customer_searched;

	protected $_permissions;
	protected $_permissionsChanges = FALSE;

	protected function _construct()
	{
		$this->_init('mailplus/user');
	}

	public function loadByCustomerId( $customer_id )
	{
		/* @var $customer Mage_Customer_Model_Customer */
		$customer = Mage::getModel('customer/customer')->load($customer_id);
		if ($customer && $customer->getId() && $customer->getId() >0 )
			return $this->loadByCustomer( $customer );

		if ($this->getId() && $this->getId()>0)
		{
			$this->clearInstance();
			$this->unsetData();
		}

		return $this;
	}

	public function loadByCustomer( Mage_Customer_Model_Customer $customer )
	{
		$field = 'customer_id';

		$customer_id = $customer->getId();
		$store_id = $customer->getStore()->getId();

		$this->_beforeLoad($customer_id, $field);
		$this->_getResource()->loadByFieldAndStore($this, $customer_id, $field, $store_id);
		$this->_afterLoad();
		$this->setOrigData();
		$this->_hasDataChanges = FALSE;

		// after load immediately set the corresponding customer object
		if ( $this->getId() && $this->getId() > 0 )
		{
			$this->setCustomer( $customer );
		}

		return $this;
	}

	/**
	 * Get the correct user by email
	 *
	 * @param $email
	 * @param null $store_id
	 * @return Techtwo_Mailplus_Model_User
	 */
	public function findByEmail($email)
	{
		$collection = $this->getCollection()
			->addFieldToFilter('email', $email);

		$user = $collection->getFirstItem();
		return $user;
	}


	/**
	 * @param Mage_Customer_Model_Customer $customer
	 * @param null $store_id
	 * @return Techtwo_Mailplus_Model_User
	 */
	public function findByCustomer( Mage_Customer_Model_Customer $customer)
	{
		$collection = $this->getCollection()
			->addFieldToFilter('customer_id', $customer->getId());

		/* @var $user Techtwo_Mailplus_Model_User */
		$user = $collection->getFirstItem();
		if ( $user && $user->getId() && $user->getId()>0 )
			$user->setCustomer($customer);
		return $user;
	}

	/**
	 * Fetches the associated customer by the MailPlus user
	 * This customer is cached in the class.
	 *
	 * @return Mage_Customer_Model_Customer|NULL or NULL if no customer was set
	 */
	public function getCustomer()
	{
		if ( $this->_customer === NULL && $this->_customer_searched === NULL )
		{
			if ( $this->getCustomerId() )
			{
				$this->_customer = Mage::getModel('customer/customer')->load($this->getCustomerId());
				$this->_customer_searched = TRUE;
				if ( !$this->_customer || $this->_customer->getId() < 1 )
				{
					$this->_customer->clearInstance();
					$this->_customer = NULL;
				}
			}
		}
		return $this->_customer;
	}

	/**
	 * Set the customer for the MailPlus (prefered over $this->getCustomerId() )
	 * @param Mage_Customer_Model_Customer $customer
	 */
	public function setCustomer( Mage_Customer_Model_Customer $customer )
	{
		// this is required for hasDataChanges to work correctly, as set will always set this on true
		if ($this->getId()<1 || $this->getCustomerId() != $customer->getId())
		{
			$this->setCustomerId($customer->getId()); // could be zero too, that's ok
		}
		$this->_customer = $customer;
		$this->_customer_searched = TRUE;
	}

	/**
	 * Removes customer reference
	 * @return Mage_Core_Model_Abstract
	 */
	function _clearReferences()
	{
		if ( $this->_customer )
		{
			$this->_customer->clearInstance();
			$this->_customer = NULL;
			$this->_customer_searched = NULL;
		}
		return parent::_clearReferences();
	}

	/**
	 * Removes all permissions of the user in MailPlus.
	 *
	 * Note you should actually not delete users , but remove all permissions
	 * This won't give errors if there was no mailplus users to begin with
	 */
	public function delete()
	{
		// TODO: QUEUE on error
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');
		try { $rest->disableContact( $this->getStoreId(), $this->getId() ); }
		catch( Techtwo_Mailplus_Client_Exception $ex ) {
			if ( 'CONTACT_NOT_FOUND' !== $ex->getType() ) {
				throw $ex;
			}
		}
		catch( Exception $ex ) {  throw $ex; }

		return parent::delete();
	}

	/**
	 * Try to synchronize the user in mailplus
	 *
	 * @return Mage_Core_Model_Abstract
	 */
	public function _afterSave()
	{
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');
		$customer = $this->getCustomer();

		$siteId = $this->getWebsiteId();

		try {		
			$rest->saveContact($siteId, $this->getStoreId(), $this->toMailplusData());
		} 
		catch( Exception $e ) {
			Mage::logException($e);
		}	

		return parent::_afterSave();
	}
	
	private function addDataToAray($attributeCode, $value, $mapping, $data) {
		if ($value && isset($mapping[$attributeCode])) {
			$data[$mapping[$attributeCode]] = $value;
		}
		return $data;
	}
	
	private function userDataToMailplusData($mapping, $contact_properties) {
		// Set this users' data
		$contact_properties = $this->addDataToAray('email', $this->getEmail(), $mapping, $contact_properties);
		$contact_properties = $this->addDataToAray('firstname', $this->getFirstname(), $mapping, $contact_properties);
		$contact_properties = $this->addDataToAray('lastname', $this->getLastname(), $mapping, $contact_properties);
		
		// Add store description dummy attribute
		$storedescription = new Mage_Customer_Model_Attribute();
		$storedescription->setAttributeCode('storedescription');
		$storedescription->setFrontendLabel(Mage::helper('mailplus')->__('Storeview description'));
		$storedescription->setIsVisible(true);
		
		$value = Mage::helper('mailplus')->attributeToMailplusValue($this, $storedescription, null);
		$contact_properties = $this->addDataToAray($storedescription->getAttributeCode() , $value, $mapping, $contact_properties);
		
		return $contact_properties;
	}
	
	/**
	 * Convert the user to mailplus data. When there is a customer linked it will load
	 * the data from the customer.
	 *
	 * @return array
	 */
	public function toMailplusData()
	{
		/* @var $config Techtwo_Mailplus_Helper_Config */
		$config = Mage::helper('mailplus/config');	
		/* @var $config Techtwo_Mailplus_Helper_Data */
		$data = Mage::helper('mailplus');
		$is_test = '1' == $this->getData('is_test');

		// Make sure to load all the data first
		if (! $this->hasData('email') )
			$this->load($this->getId());
		
		$contact_properties = array();
		$aux = array(
				'testGroup'  => (bool) $is_test,
				'externalId' => $this->getId()
		);

		$mapping = $config->getMapping($this->getWebsiteId());
		
		$contact_properties = $this->userDataToMailplusData($mapping, $contact_properties);
		
		// Set the data from the linked customer.
		$customer = $this->getCustomer();
		if ($customer) {
			$address = $customer->getPrimaryBillingAddress();
			
			if ( $address ) {
				foreach ( $address->getAttributes() as $attr ) {
					$contact_properties = $data->addAttributeToProperties($contact_properties, $attr, $address, $mapping);
				};
			}
			
			$attributes = $customer->getAttributes();
			
			/* Dummy attribute for the first purchase date */
			$firstpurchasedate = new Mage_Customer_Model_Attribute();
			$firstpurchasedate->setAttributeCode('firstpurchasedate');
			$firstpurchasedate->setFrontendLabel(Mage::helper('mailplus')->__('First purchase date'));
			$firstpurchasedate->setIsVisible(true);
			$attributes[] = $firstpurchasedate;
			
			/* Dummy attribute for the last purchase date */
			$lastpurchasedate = new Mage_Customer_Model_Attribute();
			$lastpurchasedate->setAttributeCode('lastpurchasedate');
			$lastpurchasedate->setFrontendLabel(Mage::helper('mailplus')->__('Last purchase date'));
			$lastpurchasedate->setIsVisible(true);
			$attributes[] = $lastpurchasedate;
			
			foreach ($attributes as $attr ) {
				$contact_properties = $data->addAttributeToProperties($contact_properties, $attr, $customer, $mapping);
			};
		}
		
		if ( $this->hasPermissionChanges() ) {
			$contact_properties['permissions'] = $this->toMailplusDataPermission();
		}
				
		$aux['properties'] = $contact_properties;		
		return $aux;
	}

	/**
	 * Sets a permission
	 *
	 * Note: only sets the setDataChanges on an actual change.
	 *
	 * @param $permission
	 * @param $allowed
	 * @throws Exception
	 */
	public function setPermission( $permission, $allowed )
	{
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');

		$permission = (int) $permission;
		if (!in_array($permission, $rest->getAllPermissionBits()))
		{
			throw new Exception('Invalid permission');
		}

		$allowed = (bool) $allowed;

		$this->getPermissions();

		if (is_null($this->_permissions)) {
			Mage::log("Error while getting current permissions, so not setting new permissions");
            return;
		}
		
		if ( FALSE !== $this->_permissions )
		{
			if ( array_key_exists($permission, $this->_permissions))
			{
				if ( $this->_permissions[$permission]->getEnabled() !== $allowed )
				{
					$this->setDataChanges(TRUE);
					$this->_permissionsChanges = TRUE;
					$this->_permissions[$permission]->setEnabled( $allowed );
				}
			}
			else
			{
                Mage::log("Invalid permission $permission, no such permission for contact found");
			}

		}


	}

	public function getPermissions()
	{
		if ( NULL === $this->_permissions )
		{
			/* @var $rest Techtwo_Mailplus_Helper_Rest */
			$rest = Mage::helper('mailplus/rest');

			$make_new_permission =  FALSE;

			if ( $this->getId() && $this->getId() > 0 )
			{
				$contactData = null;
				
				try {
					$contactData = $rest->getContactByExternalId( $this->getId(), $this->getStoreId() );
				} catch (Exception $e) {
					Mage::logException($e);
					return null;
				}
				
				$permissions = NULL !== $contactData && isset( $contactData->properties ) && isset( $contactData->properties->permissions )? $contactData->properties->permissions:FALSE;

				if ( $permissions )
				{
					$this->_permissions = array();
					foreach ($permissions as $permission)
					{
						$p = new Techtwo_Mailplus_Client_Contact_Permission;

						$bit = (int) $permission->bit;

						$p->setBit($bit);
						$p->setEnabled($permission->enabled);
						$p->setDescription($permission->description);

						$this->_permissions[$bit] = $p;
					}
				}
				else
				{
					$make_new_permission = TRUE;
				}
			}
			else
			{
				$make_new_permission = TRUE;
			}

			if ( $make_new_permission )
			{
				// User is new
				$this->_permissions = array();

				$allBits = $rest->getAllPermissionBits();

				foreach ($allBits as $bit)
				{
					$p = new Techtwo_Mailplus_Client_Contact_Permission;

					$p->setBit($bit);
					$p->setEnabled( FALSE );
					//$p->setDescription( '' );

					$this->_permissions[$bit] = $p;
				}
			}
		}
		return $this->_permissions;
	}

	public function isPermissionAllowed($permission)
	{
		$this->getPermissions();
		if ( array_key_exists($permission, $this->_permissions) )
		{
			/* @var $permission Techtwo_Mailplus_Client_Contact_Permission */
			$permission = $this->_permissions[$permission];
			return $permission->isEnabled();
		}
		return false;
	}

	public function hasPermissionChanges()
	{
		return NULL !== $this->_permissions && FALSE !== $this->_permissions && TRUE === $this->_permissionsChanges;
	}

	public function setPermissionsChanges($changed) {
		$this->_permissionsChanges = $changed;
	}
	
	/**
	 * Is used in toMailPlusData() , do note, toMailPlusData won't call it of no changes were made
	 *
	 * @return array|bool
	 */
	public function toMailplusDataPermission()
	{
		$permissions = $this->getPermissions();
		if ( !$permissions )
			return FALSE;

		$data = array();
		foreach ($permissions as $permission)
		{
			/* @var $permission Techtwo_Mailplus_Client_Contact_Permission */
			$data []= $permission->toMailPlusData();
		}

		return $data;
	}
	
	/**
	 * Returns the siteId where this users belongs to. When this user is linked to a customer
	 * the customer will be loaded first and the customers website will be used. When there 
	 * is no customer the storeId will be used to load the store and get the siteId from the 
	 * store.
	 * 
	 * @return int
	 */
	
	public function getWebsiteId() {
		$siteId = null;
		$customer = $this->getCustomer();
		
		if ($customer) {
			$siteId = $customer->getWebsiteId();
		} else {
			$siteId = Mage::app()->getStore($this->getStoreId())->getWebsiteId();
		}
		
		return $siteId;
	}
}
