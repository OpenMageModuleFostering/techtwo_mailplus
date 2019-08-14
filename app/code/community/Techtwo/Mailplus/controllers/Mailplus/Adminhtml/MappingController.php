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
class Techtwo_Mailplus_Mailplus_Adminhtml_MappingController extends Mage_Adminhtml_Controller_Action {

	public function indexAction(){
        $this->loadLayout()->_setActiveMenu('mailplus')
			->_addLeft($this->getLayout()->createBlock('adminhtml/template')->setTemplate('mailplus/website_switcher.phtml'))
			->_addContent($this->getLayout()->createBlock('mailplus/adminhtml_mapping'));
        
        $this->renderLayout();
    }
    
    public function saveAction() {
		$data = $this->getRequest()->getPost();
		if (!$data) {
			$this->_redirect('*/*/index/', array( 'id' => $this->getRequest()->id ));
		}
		
		/* @var $config Techtwo_Mailplus_Helper_Config */
		$config = Mage::helper('mailplus/config');
		
		$mapping = $data['groups']['mapping']['fields'];
		$siteId = $mapping['site']['value'];
		$site = Mage::app()->getWebsite($siteId);
		$usedProps = array();
		$usedAttrs = array();
		$doubleMapped = array();	
			
		foreach($mapping as $attributeName => $mailplusProp) {
			if ($mailplusProp['value'] && $mailplusProp['value'] != '') {
				if (isset($usedProps[$mailplusProp['value']])) {
					$doubleMapped[$mailplusProp['value']] = true;
				}
				
				$usedProps[$mailplusProp['value']] = $attributeName;
			}
			$usedAttrs[$attributeName] = $mailplusProp['value'];
		}

		if (count($doubleMapped) > 0) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mailplus')->__('Mapping not saved. One or more mailplus fields have been mapped more then once'));
			Mage::register('mailplus_double_mapped', $doubleMapped);
			Mage::register('mailplus_currentmapping', $usedAttrs);
			$this->indexAction();
			return;
		}
		
		$coreConfig = new Mage_Core_Model_Config();
		
		foreach($mapping as $attributeName => $mailplusProp) {
			if ($mailplusProp['value']) {
				$coreConfig->saveConfig(Techtwo_Mailplus_Helper_Config::$_MAPPING_CONFIGPATH . $attributeName, $mailplusProp['value'], 'websites', $siteId);
			} else {
				$coreConfig->saveConfig(Techtwo_Mailplus_Helper_Config::$_MAPPING_CONFIGPATH . $attributeName, null, 'websites', $siteId);
			}
		}
		
		Mage::app()->getCache()->remove($config->getMappingCacheKey($siteId));		
		// Mark as saved once so the default value for an attributeName will not be used when there is no config found. (e.g. not mapped)
		$coreConfig->saveConfig(Techtwo_Mailplus_Helper_Config::$_MAPPING_CONFIGPATH . 'savedonce', 1, 'websites', $siteId);		
		$coreConfig->cleanCache();
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mailplus')->__('Mapping saved'));
		$this->_redirect('*/*/index');
	}
}
