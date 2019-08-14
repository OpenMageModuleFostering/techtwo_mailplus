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
class Techtwo_Mailplus_Client_Contact_Property
{
	protected $_name;
	protected $_description;
	protected $_type;

	public function setData( array $data )
	{
		if ( array_key_exists('name', $data))
			$this->_name = $data['name'];
		if ( array_key_exists('description', $data))
			$this->_description = $data['description'];
		if ( array_key_exists('type', $data))
			$this->_type = $data['type'];

		return $this;
	}

	public function debug()
	{
		return array(
			'name' => $this->_name,
			'description' => $this->_description,
			'type' => $this->_type,
		);
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setName($name)
	{
		$this->_name = $name;
		return $this;
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

	public function getType()
	{
		return $this->_type;
	}

	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	}
}