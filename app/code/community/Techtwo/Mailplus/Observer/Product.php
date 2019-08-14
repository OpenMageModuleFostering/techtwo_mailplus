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
class Techtwo_Mailplus_Observer_Product
{
	/**
	 * Synchronize product with MailPlus
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function synchronize_product(Varien_Event_Observer $observer)
	{
		/* @var $product Mage_Catalog_Model_Product */
		$product = $observer->getProduct();

		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');

		try { $rest->saveProduct( $observer->getProduct() ); }
		catch (Exception $e ) { Mage::logException($e); }
	}

	public function delete_product(Varien_Event_Observer $observer)
	{
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');
		$rest->deleteProduct( $observer->getProduct() );
	}

	public function apply_review_value(Varien_Event_Observer $observer)
	{
		/* @var $review Mage_Review_Model_Review */
		$review = $observer->getEvent()->getObject();

		if ( $review->getOrigData('status_id') != $review->getStatusId() )
		{
			/* @var $rest Techtwo_Mailplus_Helper_Rest */
			$rest = Mage::helper('mailplus/rest');


			/* @var $product Mage_Catalog_Model_Product */
			$product = Mage::getModel('catalog/product')->load($review->getEntityPkValue());

			//$productCollection = $review->getProductCollection();
			//foreach ( $productCollection as $product )

			//echo "ID:".$product->getId()."<br />\r\n";
			$rest->saveProduct( $product );
		}
	}
}
