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
class Techtwo_Mailplus_Model_Cron_Hourly
{
	// Always run once per run
	protected static $_ran_daily_cron = FALSE;

	public function run()
	{
		if ( self::$_ran_daily_cron ) {
			Mage::log("MailPlus: Cron hourly already ran", Zend_Log::ERR);
			return;
		}
		
		$currentTime = Mage::getModel('core/date')->timestamp(time());
		Mage::getModel('mailplus/info')->loadByName(Techtwo_Mailplus_Helper_Cron::HOURLY_START)
			->setName(Techtwo_Mailplus_Helper_Cron::HOURLY_START)
			->setValue($currentTime)
			->save();
		
		
		self::$_ran_daily_cron = TRUE;

		$toDate = time();
		$fromDate = strtotime('-2 hours', $toDate);
			
		Mage::log('Handling modified contacts + bounces from ' . date(DateTime::ATOM, $fromDate) . ' to ' . date(DateTime::ATOM, $toDate));
		
		foreach (Mage::app()->getWebsites() as $website) {
			$this->_modifiedContacts($website->getId(), $fromDate, $toDate);
			$this->_processBounces($website->getId(), $fromDate, $toDate);
		}
		
		$currentTime = Mage::getModel('core/date')->timestamp(time());
		Mage::getModel('mailplus/info')->loadByName(Techtwo_Mailplus_Helper_Cron::HOURLY_END)
			->setName(Techtwo_Mailplus_Helper_Cron::HOURLY_END)
			->setValue($currentTime)
			->save();
	}

	/**
	 * Get mailplus bounces in magento
	 *
	 * @param $fromDate
	 * @param $toDate
	 * @return bool
	 */
	protected function _processBounces($websiteId, $fromDate, $toDate)
	{
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');
		/* @var $config Techtwo_Mailplus_Helper_Config */
		$config = Mage::helper('mailplus/config');
		$response = $rest->getBounces($websiteId, $fromDate, $toDate);
		if ( !$response )
			return FALSE !== $response;

		$mapping = $config->getMapping($websiteId);
		
		foreach ($response as $mailplus)
		{
			$contact    = $mailplus->contact;
			$properties = $contact->properties;

			$data = array(
				'mailplus_id'         => $contact->encryptedId,
				'email'               => $properties->email,
				'total_received'      => 0, //$contact->receivedCount, // no longer exists
				'is_test'             => $contact->testGroup? 1:0,
				'is_customer_alerted' => '0', // ALWAYS RESET customer alert on new bounce
				'last_bounce_date'    => date('Y-m-d H:i:s', (int) $mailplus->date),
			);

			$attributeToSync = array("firstname", "lastname", "insertion");
			
			foreach ($attributeToSync as $attribute) {
				if (isset($mapping[$attribute])) {
					$property = $mapping[$attribute];
					if (isset($properties->{$property})) {
						$data[$attribute] = $properties->{$property};
					}
				}
			} 
			
			$model = Mage::getModel('mailplus/bounce')->load( $contact->encryptedId, 'mailplus_id' );
			if ( $model->getId() )
				unset($data['mailplus_id']);
			else
				$model->unsetData();

			$model->addData($data);

			try { $model->save(); }
			catch (Exception $ex) { Mage::logException( $ex ); }

			$model->clearInstance();
			unset($model);
		}

		return TRUE;
	}

	/**
	 * Processes the modified contacts at mailplus
	 * Synchronize the changes in MailPlus back to Magento.
	 *
	 * @param $fromDate
	 * @param $toDate
	 * @return bool
	 */
	protected function _modifiedContacts($websiteId, $fromDate, $toDate)
	{
		Mage::register('mailplusModifiedContacts', true);
		
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');
		/* @var $config Techtwo_Mailplus_Helper_Config */
		$config = Mage::helper('mailplus/config');
		
		$customerAttributes = $config->getCustomerAttributes();
		$addressAttributes = $config->getAddressAttributes();
		
		$response = $rest->getModifiedContacts($websiteId, $fromDate, $toDate);
		if ( !$response ) {
			Mage::unregister('mailplusModifiedContacts');
			return FALSE!==$response;
		}

		$attributes = $config->getMapping($websiteId);
		
		/* @var $appEmulation Mage_Core_Model_App_Emulation */
		$appEmulation = Mage::getSingleton('core/app_emulation');
		
		foreach ( $response as $mailplusContact ) {
			/* @var $user Techtwo_Mailplus_Model_User */
			$user = Mage::getModel('mailplus/user')->load($mailplusContact->externalId);
			
			if ( !$user->getId() )
				continue;

			$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($user->getStoreId());
			
			$this->updateUserData($user, $mailplusContact, $attributes);
			
			$customer = $user->getCustomer();
			$address = $customer ? $customer->getPrimaryBillingAddress() : NULL;
			
			$storeId = $user->getStoreId();
			
			if ($customer) {
				foreach ( $attributes as $attributeCode => $mailplusProperty ) {
					$updateItem = NULL;
					
					$offset = NULL;
					if (preg_match(Techtwo_Mailplus_Helper_Data::IS_MULTILINE_REGEXP, $attributeCode, $matches)) {
						$attributeCode = $matches[1];
						$offset = $matches[2];
					}
					
					if (isset($customerAttributes[$attributeCode])) {
						$updateItem = $customer;
					} if (isset($addressAttributes[$attributeCode])) {
						$updateItem = $address;
					};
					
					if ($updateItem) {
						$this->updateCustomerData($updateItem, $attributeCode, $offset, $mailplusProperty, $mailplusContact);
					}
				}
				$this->updateNewsletterSubscriber($customer, null, $mailplusContact);
				$this->saveCustomer($customer, $address);
			} else {
				$this->updateNewsletterSubscriber(null, $user->getEmail(), $mailplusContact);
			}

			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
		};

		Mage::unregister('mailplusModifiedContacts');
		return TRUE;
	}

	private function saveCustomer($customer, $address) {
		if ( $customer ) {
			if ( $address ) {
				$address->setCustomer( $customer ); // required to set this, otherwise the address will not be saved properly
				try { $address->save(); }
				catch( Exception $ex) { Mage::logException( $ex ); }
			}
		
			try { $customer->save(); }
			catch( Exception $ex) { Mage::logException( $ex ); }
		}
		
		// do mind saving an mailplus user will start a synchronisation operation
		//$user->setStoreId( $customer->getStore()->getId() );
		//try { $user->save(); }
		//catch( Exception $ex) { Mage::logException( $ex ); }
	}
	
	private function updateNewsletterSubscriber($customer, $email, $mailplusContact) {
		$prop = 'permissions';
		if (isset($mailplusContact->properties->{$prop})) {
			/* @var $subscriber Mage_Newsletter_Model_Subscriber */
			$subscriber = Mage::getModel('newsletter/subscriber');
			
			if ($customer) {
				$subscriber->loadByCustomer($customer);
			} 
			if (!$subscriber->getId()) {
				if ($customer) {
					$subscriber->loadByEmail($customer->getDataUsingMethod('email'));
				} else {
					$subscriber->loadByEmail($email);
				}
			}	
			
			$permissions = $mailplusContact->properties->{$prop};
			$enableNewsletter = false;
			foreach ($permissions as $permission) {
				if ($permission->bit == Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER) {
					$enableNewsletter =	$permission->enabled;
					break;
				}
			}	
			
			if ($enableNewsletter) {
				if (!$subscriber->getId()) {
					if ($customer) {
						$subscriber->setStoreId($customer->getStoreId());
						$subscriber->setCustomerId($customer->getId());
						$subscriber->setEmail($customer->getDataUsingMethod('email'));
					}
				}
				
				if ($customer) {
					if ($subscriber->getStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
						$subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
					}
					$subscriber->save();
				} else {
					if ($subscriber->getStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {						
						$subscriber->subscribe($email);
					}
				}
			} else {
				if ($subscriber->getId() && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
					$subscriber->unsubscribe();
				}
			}
		}
	}
	
	private function updateCustomerData($updateItem, $attributeCode, $offset, $mailplusProperty, $mailplusContact) {
		if ( isset($mailplusContact->properties->{$mailplusProperty}) ) {
			$value = $mailplusContact->properties->{$mailplusProperty};
	
			switch ($attributeCode) {
				case 'gender':
					/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
					$dataHelper = Mage::helper('mailplus');
					if ( 'M' === $value )
						$value = $dataHelper->getMagentoGenderMaleId();
					elseif( 'F' === $value )
						$value = $dataHelper->getMagentoGenderFemaleId();
					else
						$value = '';
					$updateItem->setDataUsingMethod($attributeCode, $value);
					break;
				case 'dob':
					// Magento assumes we set the value for DoB in the setup locale
					$localFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
					$magentoDate = Mage::app()->getLocale()->date($value)->toString($localFormat);					
					$value = $magentoDate;
					$updateItem->setDataUsingMethod($attributeCode, $value);
					break;
				// Do not update the next attributes
				case 'email': break;
				case 'storedescription': break;
				case 'firstpurchasedate': break;
				case 'lastpurchasedate': break;
				case 'group_id': break;
				default:					
					if ($offset !== NULL) {
						// Multiline attribute. Set the correct line
						$array = $updateItem->getDataUsingMethod($attributeCode);
						$array[$offset - 1] = $value;
						$updateItem->setDataUsingMethod($attributeCode, $array);
					} else {
						$updateItem->setDataUsingMethod($attributeCode, $value);
					}
			}
		}	
	}
	
	private function updateUserData($user, $mailplusContact, $attributes) {
		if (isset($attributes['firstname'])) {
			$firstnameProp = $attributes['firstname'];
			if ($firstnameProp) {
				if (isset($mailplusContact->properties->{$firstnameProp})) {
					$user->setFirstname($mailplusContact->properties->{$firstnameProp});
				}
			}
		}

		if (isset($attributes['lastname'])) {
			$lastnameProp = $attributes['lastname'];
			if ($lastnameProp) {
				if (isset($mailplusContact->properties->{$lastnameProp})) {
					$user->setLastname($mailplusContact->properties->{$lastnameProp});
				}
			}
		}
	}
	
}
