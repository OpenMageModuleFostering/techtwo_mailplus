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
class Techtwo_Mailplus_ImageController extends Mage_Core_Controller_Front_Action
{
	public function getAction() {
		$params = $this->getRequest()->getParams();
		
		$productId = $params['id'];
		$formatParam = $params['f'];
		
		if ($productId && $formatParam) {
			$image = Mage::helper('catalog/image');
			$mailplus = Mage::helper('mailplus');
			$mpProduct = Mage::getModel('mailplus/product')->load($productId);
			
			if ($mpProduct && $mpProduct->getId()) {
				$product = Mage::getModel('catalog/product')->load($mpProduct->getCatalogProductEntityId());
				
				$format = Techtwo_Mailplus_Helper_Data::SMALL_IMAGE_MAX;
				if ($formatParam == 'l') {
					$format = Techtwo_Mailplus_Helper_Data::LARGE_IMAGE_MAX;
				}
				
				$image = $image->init($product, 'small_image');
				$url = $image
					->keepFrame( '1' === Mage::getStoreConfig('mailplus/advanced/image_keep_frame') )
					->resize($format);
				
				$this->getResponse()->setRedirect($url);
				return;
			}
		}
		
		$this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
		$this->getResponse()->setHeader('Status','404 File not found');
	}
}