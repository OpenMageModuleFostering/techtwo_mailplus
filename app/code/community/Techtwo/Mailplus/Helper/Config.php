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
class Techtwo_Mailplus_Helper_Config extends Mage_Core_Helper_Abstract {
	public static $_MAPPING_CONFIGPATH = "mailplusmapping/mapping/";
	public static $_MAPPING_CACHE_KEY = "magento-mailplus-mapping";
	
	public function getMappingCacheKey($siteId) {
		return Techtwo_Mailplus_Helper_Config::$_MAPPING_CACHE_KEY . '-' . $siteId;
	}
	
	public static $_defaultMapping = array(
			'email'		=> 'email',
			'firstname'	=> 'firstName',
			'middlename'=> 'infix',
			'lastname'	=> 'lastName',
			'gender'	=> 'gender',
			'dob'		=> 'birthday',
			'group_id'	=> 'customerType', 
			
			
			'company'	=> 'organisation',
			'city'		=> 'city',
			'housenumber'	=> 'houseNumber',
			'telephone'	=> 'phoneNumber',
			'street'	=> 'street',
			'street-line-1'	=> 'street',
			'street-line-2' => 'houseNumber',
			'postcode' => 'postalCode',
			'country'	=> 'country',
			
			'storedescription' => 'taal',
			'firstpurchasedate' => 'firstPurchaseDate',
			'lastpurchasedate' => 'lastPurchaseDate'
	);
	
	public function getCustomerAttributes() {
		$attributes = Mage::getModel('customer/customer')->getAttributes();
		$mapping = array();
		foreach ($attributes as $attr) {
			if ($attr->getIsVisible() && $attr->getFrontendInput() !== 'hidden') {
				$mappingId = $attr->getAttributeCode();
				$mapping[$mappingId] = $attr;
			}
		}
	
		/* Dummy attribute for the storedescription (website-stroregroup-store) */
		$storedescription = new Mage_Customer_Model_Attribute();
		$storedescription->setAttributeCode('storedescription');
		$storedescription->setFrontendLabel($this->__('Storeview description'));
		$storedescription->setIsVisible(true);
		$mapping[$storedescription->getAttributeCode()] = $storedescription;		
		
		/* Dummy attribute for the first purchase date */
		$firstpurchasedate = new Mage_Customer_Model_Attribute();
		$firstpurchasedate->setAttributeCode('firstpurchasedate');
		$firstpurchasedate->setFrontendLabel($this->__('First purchase date'));
		$firstpurchasedate->setIsVisible(true);
		$mapping[$firstpurchasedate->getAttributeCode()] = $firstpurchasedate;
		
		/* Dummy attribute for the last purchase date */
		$lastpurchasedate = new Mage_Customer_Model_Attribute();
		$lastpurchasedate->setAttributeCode('lastpurchasedate');
		$lastpurchasedate->setFrontendLabel($this->__('Last purchase date'));
		$lastpurchasedate->setIsVisible(true);
		$mapping[$lastpurchasedate->getAttributeCode()] = $lastpurchasedate;
		
		return $mapping;
	}
	
	public function getAddressAttributes() {
		$customerAttrs = $this->getCustomerAttributes();
	
		$attributes = Mage::getModel('customer/address')->getAttributes();
	
		foreach ($attributes as $attr) {
			if ($attr->getIsVisible() && $attr->getFrontendInput() !== 'hidden') {
				$mappingId = $attr->getAttributeCode();
				// Only add them if they do not exist for a customer
				if (!array_key_exists($mappingId, $customerAttrs)) {
					$mapping[$mappingId] = $attr;
				}
			}
		}
	
		return $mapping;
	}
	
	/**
	 * Retrieves mapping
	 *
	 * Note for some fields:
	 *  housenumber is not an magento option. It's set as an street array. So the second you should use as housenumber
	 *  country is not an magento option, note you must set it yourselves since country_id!
	 *
	 */
	public function getMapping($websiteId)
	{
		$cache = Mage::app()->getCache();
	
		$mappingData = $cache->load($this->getMappingCacheKey($websiteId));
		if ($mappingData) {
			$mapping = unserialize($mappingData);
			
			if ($mapping) {
				return $mapping;
			}
		}
	
		$site = Mage::app()->getWebsite($websiteId);
	
		$fieldsToMap = $this->getAddressAttributes();
		$fieldsToMap = array_merge($fieldsToMap, $this->getCustomerAttributes());
		
		$mapping = array();
	
		foreach($fieldsToMap as $attr) {
			if ($attr->getFrontendInput() === 'multiline') {
				for ($i = 1; $i <= $attr->getMultilineCount(); $i++) {
					$attrCode = $attr->getAttributeCode() . '-line-' . $i;
					$mailplusProperty = $this->getMappingForAttributeCode($site, $attrCode);
					if ($mailplusProperty) {
						$mapping[$attrCode] = $mailplusProperty;
					};
				}			
			} else {
				$attrCode = $attr->getAttributeCode();
				$mailplusProperty = $this->getMappingForAttributeCode($site, $attrCode);
				if ($mailplusProperty) {
					$mapping[$attrCode] = $mailplusProperty;
				};
			}
		}
	
		$cache->save(serialize($mapping), $this->getMappingCacheKey($websiteId), array("mailplus-cache"), NULL);
	
		return $mapping;
	}
	
	/**
	 * Retrieves mapping for an attributeCode
	 *
	 * This will get the mapping from the saved configuration. If the mapping configuration
	 * has not been saved yet, default values will be used
	 *
	 */
	
	public function getMappingForAttributeCode($site, $mappingId) {
		$value = $site->getConfig(Techtwo_Mailplus_Helper_Config::$_MAPPING_CONFIGPATH . $mappingId);
	
		if (!$value && !$site->getConfig(Techtwo_Mailplus_Helper_Config::$_MAPPING_CONFIGPATH . 'savedonce')) {
			if (isset(Techtwo_Mailplus_Helper_Config::$_defaultMapping[$mappingId])) {
				$value = Techtwo_Mailplus_Helper_Config::$_defaultMapping[$mappingId];
			}
		}
		
		return $value;
	}
	
	public function syncActiveForSite($siteId) {
		return (Mage::app()->getWebsite($siteId)->getConfig('mailplus/general/active') == 1);
	}

	public function syncActiveForStore($storeId) {
		return (Mage::getStoreConfig('mailplus/general/active', $storeId) == 1);
	}

	public function contactSyncAllowedForSite($siteId) {
		$site = Mage::app()->getWebsite($siteId);
		return ($site->getConfig('mailplus/general/synchronize') == 'all');
	}
	
	public function contactSyncAllowedForStore($storeId) {
		return (Mage::getStoreConfig('mailplus/general/synchronize', $storeId) == 'all');
	}

	public function getProductSpecGroup($siteId) {
		$site = Mage::app()->getWebsite($siteId);
                $groupName = $site->getConfig('mailplus/syncsettings/productspecs');
		if ($groupName == '')
			return NULL;
		return $groupName;	
	}
}
