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
 * @method Techtwo_Mailplus_Model_System_Campaign setEncryptedId($value)
 * @method string getEncryptedId()
 * @method Techtwo_Mailplus_Model_System_Campaign setName($value)
 * @method string getName()
 */
class Techtwo_Mailplus_Model_System_Campaign extends Varien_Object
{
	protected $_triggers = array();

	public function addTrigger( $encryptedId, $name )
	{
		$this->_triggers[$encryptedId] = $name;
		return $this;
	}

	public function hasTriggers()
	{
		return count($this->_triggers) > 0;
	}

	/**
	 * @return array
	 */
	public function getTriggers()
	{
		return $this->_triggers;
	}

	public function getFirstTriggerName()
	{
		if ( 0 === count($this->_triggers) )
			return NULL;

		$trigger = reset($this->_triggers);
		return $trigger;
	}

	public function getFirstTriggerEncryptedId()
	{
		if ( 0 === count($this->_triggers) )
			return NULL;

		$trigger = reset($this->_triggers);
		return key($this->_triggers);
	}
}