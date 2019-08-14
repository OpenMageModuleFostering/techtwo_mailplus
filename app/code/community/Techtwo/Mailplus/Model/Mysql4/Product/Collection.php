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
class Techtwo_Mailplus_Model_Mysql4_Product_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('mailplus/product');
	}

	public function getAllStoreIdsByProductId( $product_id )
	{
		$db = $this->getConnection();

		$select = new Varien_Db_Select( $db );
		$select->from( $this->getMainTable(), array('store_id') )->where( 'catalog_product_entity_id = ?', $product_id );
		//echo $select."<br />\r\n";


		//$this->_fetchAll( $select ); // could be used for automatic cache
		$result = $db->fetchCol( $select );

		// echo "<pre>" . print_r($result, true) . "</pre>";

		return $result;

	}
}