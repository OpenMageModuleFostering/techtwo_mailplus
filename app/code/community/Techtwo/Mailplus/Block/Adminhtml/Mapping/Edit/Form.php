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
class Techtwo_Mailplus_Block_Adminhtml_Mapping_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form(array(
				'id' => 'edit_form',
				'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
				'method' => 'post',
				'enctype' => 'multipart/form-data'
		)
		);
		
		$website = Mage::app()->getFrontController()->getRequest()->getParam('website');
		if (!$website) {
			$sites = Mage::app()->getWebsites();
			$website = reset($sites);
		} else {
			$website = Mage::app()->getWebsite($website);
		}
		
		$fieldSet = $form->addFieldset('Mapping', array('legend' => 'Mapping for site ' . $website->getName()));
		
		$this->fillForm($website, $fieldSet);
	
		$form->setUseContainer(true);
		$this->setForm($form);
		return parent::_prepareForm();
	}
	
	protected function addAttributeToFieldset($site, $fieldset, $attr, $mailplusProperties) {
		if ($attr->getFrontendInput() === 'multiline') {
			for ($i = 1; $i <= $attr->getMultilineCount(); $i++) {
				$mappingId = $attr->getAttributeCode() . '-line-' . $i;
				$label = $attr->getFrontendLabel() . ' ' . Mage::helper('mailplus')->__('line') . ' ' . $i;
				$this->addAttributeCodeToFieldset($mappingId, $label, $site, $fieldset, $attr, $mailplusProperties);
			}
		} else {			
			$mappingId = $attr->getAttributeCode();
			$label = $attr->getFrontendLabel();
			$this->addAttributeCodeToFieldset($mappingId, $label, $site, $fieldset, $attr, $mailplusProperties);
		}
	}

	protected function addAttributeCodeToFieldset($mappingId, $label, $site, $fieldset, $attr, $mailplusProperties) {
		$currentMapping = Mage::registry('mailplus_currentmapping');
		$doubleMappedProps = Mage::registry('mailplus_double_mapped');
		
		$value = null;
		if ($currentMapping) {
			$value = $currentMapping[$mappingId];
		}
		
		if (!$value) {
			$value = $site->getConfig(Techtwo_Mailplus_Helper_Config::$_MAPPING_CONFIGPATH . $mappingId);
		}
		
		if (!$value && !$site->getConfig(Techtwo_Mailplus_Helper_Config::$_MAPPING_CONFIGPATH . 'savedonce')) {
			if (isset(Techtwo_Mailplus_Helper_Config::$_defaultMapping[$mappingId])) {
				$value = Techtwo_Mailplus_Helper_Config::$_defaultMapping[$mappingId];
			}
		}
		
		$fieldOptions = array(
				'backend_model' => 'mailplus_mapping',
				'label' => $label,
				'title' => $label,
				'name' => 'groups[mapping][fields][' . $mappingId . '][value]',
				'options' => $mailplusProperties,
				'value' => $value
		);
		
		if (isset($doubleMappedProps[$value])) {
			$fieldOptions['after_element_html'] = '<div class="validation-advice">'.
					Mage::helper('mailplus')->__('This property is mapped more than once') .
					'</div>';
		}
		
		$fieldset->addField('mailplusmapping_mailplusmapping_' . $mappingId, 'select', $fieldOptions);
	}
	
	protected function fillForm($site, $fieldset) {
		/* @var $rest Techtwo_Mailplus_Helper_Rest */
		$rest = Mage::helper('mailplus/rest');
		/* @var $mailplus Techtwo_Mailplus_Helper_Data */
		$mailplus = Mage::helper('mailplus');
		/* @var $config Techtwo_Mailplus_Helper_Config */
		$config = Mage::helper('mailplus/config');
		
		$websiteId = $site->getId();
		
		$properties = null;
		try {
			$rest->clearContactPropertiesCache($websiteId);
			$properties = $rest->getContactProperties($websiteId);
		} catch (Exception $e) {
			Mage::logException($e);
		}
		
		if (!$properties) {
			$fieldset->addField("label", "label", array(
					'label' => $mailplus->__('Error:'),
					'value' => $mailplus->__('Could not get settings from MailPlus. '))
			);
			return;
		}
		
		$propoptions = array();
		$propoptions[''] = $mailplus->__('Not mapped');
		foreach($properties as $prop) {
			if ($prop->getName() !== 'testGroup' &&
					$prop->getName() !== 'externalId' &&
					$prop->getName() !== 'permissions') {				
				$propoptions[$prop->getName()] = $prop->getDescription();
			}
		}
		
		$fieldset->addField('custlabel', 'label', array('bold' => true,	'value' => $mailplus->__('Customer')));
		$attributes = $config->getCustomerAttributes();
		foreach ($attributes as $attr) {
			if ($attr->getIsVisible() && $attr->getAttributeCode() !== 'disable_auto_group_change') {
				$this->addAttributeToFieldset($site, $fieldset, $attr, $propoptions);
			}
		}
		$fieldset->addField('addrlabel', 'label', array('bold' => true,	'value' => $mailplus->__('Address')));
		$attributes = $config->getAddressAttributes();
		foreach ($attributes as $attr) {
			if ($attr->getIsVisible()) {
				$this->addAttributeToFieldset($site, $fieldset, $attr, $propoptions);
			}
		}
		
		$fieldset->addField('mailplusmapping_mapping_site', 'hidden',
				array(
						'name' => 'groups[mapping][fields][site][value]',
						'value' => $site->getId()
				)
		);
	}
}