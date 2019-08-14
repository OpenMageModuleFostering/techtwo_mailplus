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
class Techtwo_Mailplus_Model_System_Config_Source_Product_Specs
{
	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$h = Mage::helper('mailplus');
		$groupNames = $h->getAllAttributeGroupNames();

		$options = array();
		$options[] = array('value' => '', 'label' => 'Selecteer een groep');
		foreach($groupNames as $name) {
			$options[] = array('value' => $name, 'label' => $name);
		}

		return $options;
	}
}
