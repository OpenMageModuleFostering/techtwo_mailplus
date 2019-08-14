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
 * Used in creating options for Yes|No config value selection
 *
 */
class Techtwo_Mailplus_Model_System_Config_Source_Yesno_Default_Yes
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Yes').' ('.Mage::helper('adminhtml')->__('Recommended').')'),
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('No')),
        );
    }

}
