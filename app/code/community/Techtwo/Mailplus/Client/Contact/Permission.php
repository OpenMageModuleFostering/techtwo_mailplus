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
class Techtwo_Mailplus_Client_Contact_Permission
{
	protected $_bit;
	protected $_enabled;
	protected $_description;

	public function getBit()
	{
		return $this->_bit;
	}

	public function setBit($bit)
	{
		$this->_bit = (int)$bit;
		return $this;
	}

	public function getEnabled()
	{
		return $this->_enabled;
	}

	public function setEnabled($enabled)
	{
		$this->_enabled = (bool)$enabled;
		return $this;
	}

	public function isEnabled()
	{
		return (bool) $this->_enabled;
	}

	public function getDescription()
	{
		return $this->_description;
	}

	public function setDescription($description)
	{
		$this->_description = $description;
		return $this;
	}

	public function toMailPlusData()
	{
		$aux = array(
			'bit'         => $this->getBit(),
			'enabled'     => $this->getEnabled(),
			'description' => $this->getDescription()
		);

		if ( !$aux['description'] )
			unset($aux['description']);

		return $aux;
	}
}