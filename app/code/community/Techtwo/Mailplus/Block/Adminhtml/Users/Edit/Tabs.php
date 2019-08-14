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
class Techtwo_Mailplus_Block_Adminhtml_Users_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
 
    public function __construct()
    {
        parent::__construct();
        $this->setId('mailplus_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('mailplus')->__('Customer Information'));
    }
 
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('mailplus')->__('Customer Information'),
            'title'     => Mage::helper('mailplus')->__('Customer Information'),
            'content'   => $this->getLayout()->createBlock('mailplus/adminhtml_users_edit_tab_form')->toHtml(),
        ));
       
        return parent::_beforeToHtml();
    }
}