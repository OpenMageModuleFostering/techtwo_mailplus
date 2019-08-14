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
class Techtwo_Mailplus_Helper_Feed extends Mage_Core_Helper_Abstract
{
	public function getAllOptions( $store_id =NULL )
	{
		$attr_feed = Mage::getModel('catalog/product')->getResource()->getAttribute('mailplus_feed');
		
		if ( NULL !== $store_id )
			$attr_feed->setStoreId( $store_id );
		
		/* @var $attr_feed Mage_Catalog_Model_Resource_Eav_Attribute */
		$options = $attr_feed
		->getSource()
		->getAllOptions();
		
		$valid_feeds = array();
		foreach ( $options as $option )
			if ('' != $option['value'])
			$valid_feeds[$option['value']] = $option['label'];
		return $valid_feeds;
	}
	
	
	public function getProductIdsSql( $feed_id )
	{
		
		$db = Mage::getSingleton('core/resource')->getConnection('mailplus_read');
		return '
			SELECT `catalog_product_entity_id`
			FROM `'.Mage::getModel('mailplus/feed')->getCollection()->getTable('mailplus/feed').'`
			WHERE eav_attribute_option_id = '.$db->quote($feed_id).'
			ORDER BY `order`';
	}
	
	
	public function getProductIds( $feed_id )
	{
		
		$db = Mage::getSingleton('core/resource')->getConnection('mailplus_read');
		$productIds = $db->fetchPairs( '
			SELECT `catalog_product_entity_id`, NULL as `foo`
			FROM `'.Mage::getModel('mailplus/feed')->getCollection()->getTable('mailplus/feed').'`
			WHERE eav_attribute_option_id = '.$db->quote($feed_id).'
			ORDER BY `order`'
		);
		
		return is_bool($productIds)? $productIds:array_keys($productIds);
	}
}
?>