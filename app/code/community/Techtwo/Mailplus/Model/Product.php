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
 * @method float getPrice()
 * @method Techtwo_Mailplus_Model_Product setPrice( $value )
 * @method string getCreatedAt()
 * @method Techtwo_Mailplus_Model_Product setCreatedAt( $value )
 * @method string getUpdatedAt()
 * @method Techtwo_Mailplus_Model_Product setUpdatedAt( $value )
 * @method string getStoreId()
 * @method Techtwo_Mailplus_Model_Product setStoreId( $value )
 * @method Techtwo_Mailplus_Model_Mysql4_Product_Collection getCollection()
 */
class Techtwo_Mailplus_Model_Product extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		$this->_init('mailplus/product');
	}

	/**
	 * @param $product_id
	 * @param null $store_id
	 * @return Techtwo_Mailplus_Model_Product
	 */
	public function findByProductId( $product_id, $store_id=NULL )
	{
		if ( NULL == $store_id )
		{
			$store_id = Mage::app()->getStore()->getId();
		}

		$collection = $this->getCollection()
			->addFieldToFilter('catalog_product_entity_id', $product_id)
			->addFieldToFilter('store_id', $store_id);

		$product = $collection->getFirstItem();
		return $product;
	}

	public function getExternalId()
	{
		return $this->getStoreId().'-'.$this->getId();
	}
}
