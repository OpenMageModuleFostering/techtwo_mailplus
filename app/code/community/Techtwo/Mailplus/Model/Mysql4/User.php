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
class Techtwo_Mailplus_Model_Mysql4_User extends Mage_Core_Model_Mysql4_Abstract
{
	//protected $_isPkAutoIncrement = false;

    public function _construct()
    {   
		$this->_init('mailplus/user', 'user_id');
    }

	/**
	 * Load an object by field and store id
	 *
	 * Actually the same as load, but with an additional check for where
	 *
	 * @param Mage_Core_Model_Abstract $object
	 * @param $value
	 * @param $field
	 * @param $store_id
	 * @return Techtwo_Mailplus_Model_Mysql4_User
	 */
	public function loadByFieldAndStore(Mage_Core_Model_Abstract $object, $value, $field, $store_id)
	{
		$read = $this->_getReadAdapter();
		if ($read && !is_null($value)) {

			$select = $this->_getReadAdapter()->select()
				->from($this->getMainTable())
				->where($this->getMainTable().'.'.$field.'=?', $value)
				->where($this->getMainTable().'.'.'store_id'.'=?', $store_id)
			;

			$data = $read->fetchRow($select);

			if ($data) {
				$object->setData($data);
			}
		}

		$this->unserializeFields($object);
		$this->_afterLoad($object);

		return $this;
	}
}