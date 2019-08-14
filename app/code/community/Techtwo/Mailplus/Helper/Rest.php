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
class Techtwo_Mailplus_Helper_Rest extends Mage_Core_Helper_Abstract
{
	const PERMISSION_BIT_NEWSLETTER = 1;

	/** The CAMPAIGN_ constants name are named after the configuration name */

	/** */
	const CAMPAIGN_NEWSLETTER        = 21;
	const CAMPAIGN_ABANDONED_CART    = 22;
	const CAMPAIGN_PRODUCT_REVIEW    = 23;

	/**
	 *
	 * Create $data:
	 * array products = product_ids involved at order
	 * float value    = total order value
	 */
	const CONVERSION_SHOPPINGCART = 'SHOPPINGCART';

	/**
	 * Cached clients stores based
	 * @var array(Zend_Rest_Client) */
	protected $_client_stores = array();

	/**
	 * Cached clients website based
	 * @var array(Zend_Rest_Client) */
	protected $_client_websites = array();

	protected $_restBaseUrl;

	/**
	 * Checks if rest is enabled
	 *
	 * The store configuration rest must be filled in!
	 *
	 * @param int $store_id
	 * @param int $website_id
	 * @throws Exception
	 * @return bool
	 */
	protected function _isEnabled($store_id, $website_id)
	{
		if ($store_id && $website_id)
		{

		}
		elseif ($store_id !== NULL)
		{
			return '1' === Mage::getStoreConfig('mailplus/general/active', $store_id) && '' != Mage::getStoreConfig('mailplus/general/rest_consumer_key', $store_id) && '' != Mage::getStoreConfig('mailplus/general/rest_secret', $store_id);
		}
		elseif ($website_id !== NULL)
		{
			return  '1' === Mage::app()->getWebsite($website_id)->getConfig('mailplus/general/active') && '' != Mage::app()->getWebsite($website_id)->getConfig('mailplus/general/rest_consumer_key') && '' != Mage::app()->getWebsite($website_id)->getConfig('mailplus/general/rest_secret');
		}

		Mage::logException(new Exception('Use either store_id or website id'));
		throw new Exception('Use either store_id or website id');
	}

	/**
	 * @param $store_id
	 * @return Techtwo_Mailplus_Client_Rest
	 */
	public function getClientByStore($store_id)
	{
		return $this->_getClient($store_id);
	}

	/**
	 * @param $website
	 * @return Techtwo_Mailplus_Client_Rest
	 */
	public function getClientByWebsite($website)
	{
		return $this->_getClient(NULL, $website);
	}

	protected function _restBaseUrl()
	{
		if ( NULL === $this->_restBaseUrl )
		{
			$in_test_mode        = '1' === ((string) Mage::getConfig()->getNode('mailplus/in_test_mode'));
			$this->_restBaseUrl  = (string) $in_test_mode? Mage::getConfig()->getNode('mailplus/rest_base_test'):Mage::getConfig()->getNode('mailplus/rest_base');
		}
		return $this->_restBaseUrl;
	}

	/**
	 * Retrieves rest client connected with oAuth to MailPlus
	 * Initializes on first access.
	 *
	 * @param int $store_id
	 * @param int $website_id
	 * @throws Exception
	 * @return Techtwo_Mailplus_Client_Rest
	 */
	public function _getClient($store_id=NULL, $website_id=NULL)
	{
		if ($store_id === NULL && $website_id === NULL)
			throw new Exception('Use either store_id or website id');

		if ( !$this->_isEnabled($store_id, $website_id) )
		{
			return NULL;
		}

		$this->_client_stores = array();
		$this->_client_websites = array();

		if (
			($store_id && !array_key_exists($store_id,$this->_client_stores))
			|| ($website_id && !array_key_exists($website_id,$this->_client_websites) )
		)
		{  // we don't use the two-way http://framework.zend.com/manual/1.12/en/zend.oauth.introduction.html

			$in_test_mode    = '1' === ((string) Mage::getConfig()->getNode('mailplus/in_test_mode'));
			$restBaseDomain  = $in_test_mode? Mage::getConfig()->getNode('mailplus/rest_test'):Mage::getConfig()->getNode('mailplus/rest');

			if ($store_id)
			{
				$consumer_key = Mage::getStoreConfig('mailplus/general/rest_consumer_key',$store_id);
				$secret       = Mage::getStoreConfig('mailplus/general/rest_secret',$store_id);
			}
			else
			{
				$consumer_key = Mage::app()->getWebsite($website_id)->getConfig('mailplus/general/rest_consumer_key');
				$secret       = Mage::app()->getWebsite($website_id)->getConfig('mailplus/general/rest_secret');
			}

			$configOauth = array(
				// 'callbackUrl'  => Mage::getUrl('*/*/callback'),
				// 'siteUrl'      => $restBaseDomain, // no need for 2-way
				'requestScheme'   => Zend_Oauth::REQUEST_SCHEME_HEADER,
				'consumerKey'     => $consumer_key,
				'consumerSecret'  => $secret,
				'version'         => '1.0',
				'signatureMethod' => 'HMAC-SHA1',
			);

			$config = array(
				'useragent' => 'Magento-MailPlus v'.Mage::getConfig()->getNode('modules/Techtwo_Mailplus/version')
			);

			$token = new MailPlus_Oauth_Token_Access();
			$httpClient = $token->getHttpClient($configOauth, NULL, $config);
			$httpClient->setHeaders('Content-Type','application/json');
			$httpClient->setHeaders('Accept','application/json');
			$httpClient->setHeaders('Accept-encoding','');
			$httpClient->setKeepContentType();

			$client = new Techtwo_Mailplus_Client_Rest($restBaseDomain);
			$client->setHttpClient($httpClient);
			
			
			if ($store_id) {
				$client->setSiteId( Mage::getModel('core/store')->load( $store_id)->getWebsiteId() );
			} else {
				$client->setSiteId($website_id);
			}
			
			if ($store_id) {
				$this->_client_stores[$store_id] = $client; 
			}
			else {
				$this->_client_websites[$website_id] = $client;
			}
		}

		if ($store_id)
			return $this->_client_stores[$store_id];

		return $this->_client_websites[$website_id];
	}

	protected  $_trigger_campaign_state;

	public function getTriggerCampaignLastSate()
	{
		return $this->_trigger_campaign_state;
	}

	/**
	 * Triggers a campaign
	 *
	 * @param int $campaign_constant A campaign constant defined in this class
	 * @param int $externalId        The customer external id
	 * @return bool
	 */
	public function triggerCampaign( $campaign_constant, $externalId, array $extraData=array() )
	{
		/* @var $configHelper Techtwo_Mailplus_Helper_Config */
		$configHelper = Mage::helper('mailplus/config');
		
		$this->_trigger_campaign_state = NULL;

		if ( !$this->_validateConstant('CAMPAIGN_', $campaign_constant, $constant_name) )
			Mage::throwException('Incorrect permission, see CAMPAIGN_ constants');

		/* @var $user Techtwo_Mailplus_Model_User */
		$user = Mage::getModel('mailplus/user');
		$user->load($externalId);
		
		if ( !$user || $user->getId() < 1 )	{
			$exception = new Exception("Campaign triggered '$constant_name', but user $externalId did not exist");
			Mage::logException($exception);
			return TRUE; // success but no user to send
		}

		$storeId = $user->getStoreId();

		if (!$configHelper->contactSyncAllowedForStore($storeId)) {
			return TRUE;
		}
		
		$config_path = 'mailplus/campaign/'.strtolower(substr($constant_name, strlen('CAMPAIGN_')));
		$campaign_code = Mage::getStoreConfig($config_path, $storeId);
		if (!$campaign_code)
		{   // No campaign was setup
			$this->_trigger_campaign_state = "MailPlus: Campaign $constant_name was not setup";
			Mage::log("MailPlus: Campaign $constant_name was not setup", Zend_Log::DEBUG);
			return TRUE;
		}

		$client = $this->getClientByStore($storeId);
		if ($client == NULL) {
			Mage::log("Plugin not active for storeview " . $storeId);
			return TRUE;
		}
		
		$campaigns = $this->getTriggers($client);
		
		// Incorrect campaign code, check configuration
		if ( !array_key_exists($campaign_code, $campaigns) )
		{
			//echo "Mage::getConfig('$config_path'): ".$campaign_code; die();
			Mage::log("MailPlus: Error campaign $campaign_code does not exists", Zend_Log::ERR);
			$this->_trigger_campaign_state = "MailPlus: Error campaign $campaign_code does not exists";
			return TRUE;
		}

		/* @var $campaign Techtwo_Mailplus_Model_System_Campaign */
		$campaign = $campaigns[$campaign_code];
		$campaignId = $campaign->getFirstTriggerEncryptedId();

		$extraData['externalContactId'] = $externalId;

		$response = $this->restPost($client, $this->_restBaseUrl().'/campaign/trigger/'.$campaignId, json_encode($extraData));
		$client->log(__METHOD__."('$campaign_constant' [{$campaign->getName()}])");

		if ($response == NULL) {
			return FALSE; // call failed or queued
		}
		
		return $response->isSuccessful();
	}

	protected function _validateConversionType( $type )
	{
		if (! in_array($type, array(self::CONVERSION_SHOPPINGCART)) )
		{
			Mage::throwException("Invalid conversion type '$type'");
		}
	}

	/**
	 * Create a conversion and remember it in the session
	 *
	 * @param $conversion_type
	 * @param $mailplus_id
	 * @return bool
	 */
	public function registerConversion( $conversion_type, $mailplus_id )
	{
		return $this->_conversion( $conversion_type, $mailplus_id );
	}

	/**
	 * Convert a conversion
	 *
	 * @param $conversion_type
	 * @param $mailplus_id
	 * @param array $conversion_data
	 * @return bool
	 */
	public function convertConversion( $conversion_type, $mailplus_id, array $conversion_data )
	{
		return $this->_conversion( $conversion_type, $mailplus_id, $conversion_data);
	}

	/**
	 * Registers or creates the conversion.
	 *
	 * @param $conversion_type
	 * @param $mailplus_id
	 * @param array  $convert_data     On array the conversion is created, on NULL it's only registered
	 * @return bool
	 */
	protected function _conversion( $conversion_type, $mailplus_id, array $convert_data=NULL )
	{
		$client = $this->getClientByStore( Mage::app()->getStore()->getId() );
		if (!$client)
			return TRUE;

		$this->_validateConversionType( $conversion_type );

		$action = NULL===$convert_data? 'CREATE':'CONVERT';

		$data = array(
			'mailplusId'      => $mailplus_id,
			'type'            => $action.'_'.self::CONVERSION_SHOPPINGCART,
			'interactionDate' => date(DateTime::ATOM)
		);

		if ( $convert_data )
		{
			$data = array_merge($convert_data, $data); // data last, you should not change the $data object
		}

		$response = $this->restPost($client, $this->_restBaseUrl().'/conversion', json_encode($data));
		$client->log(__METHOD__.'('.$conversion_type.')');
		
		if ($response == null) {
			return false; // Call failed or call is queued
		}
		
		$success = 204 === $response->getStatus();

		return $success;
	}


	/**
	 * Saves a product in MailPlus
	 *
	 * @throws Exception
	 * @param Mage_Catalog_Model_Product $product
	 * @return bool
	 */
	public function saveProduct( Mage_Catalog_Model_Product $product )
	{
		if ( Mage::app()->getStore()->isAdmin() ) {
			/* @var $appEmulation Mage_Core_Model_App_Emulation */
			$appEmulation = Mage::getSingleton('core/app_emulation');

			$requestStoreId = Mage::app()->getFrontController()->getRequest()->getParam('store');
			$storeIds = array();
			if ( $requestStoreId ) {
				$storeIds[] = $requestStoreId;
			} else {
				$storeIds = $product->getStoreIds();
			}
			
			foreach($storeIds as $store_id){
				if (!$this->getClientByStore($store_id)) {
					// Connector not active for current store
					continue;
				}
				
				/* @var $store Mage_Core_Model_Store */
				$store = Mage::app()->getStore($store_id);
				
				if (!$store->getIsActive())
					continue;

				$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());

				/* @var $localProduct Mage_Catalog_Model_Product */
				$localProduct = Mage::getModel('catalog/product')->load($product->getId());

				try {
					if ($localProduct->getId())	{
						$this->_saveProduct($localProduct, $store->getId());
					} 
				}
				catch (Exception $ex) {
					Mage::logException($ex);
				}

				$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
			}
		} else {
			$this->_saveProduct($product, $product->getStore()->getId());
		}
	}


	/**
	 * Saves a product in MailPlus
	 *
	 * @throws Exception
	 * @param Mage_Catalog_Model_Product $product
	 * @param int $store_id
	 * @return bool
	 */
	public function _saveProduct( Mage_Catalog_Model_Product $product, $store_id )
	{
		/* @var $productHelper Techtwo_Mailplus_Model_Product */
		$productHelper = Mage::getModel('mailplus/product');

		$client = $this->getClientByStore( $store_id );
		if (!$client)
		{   // No MailPlus client is setup or active. So we don't need to synchronize this
			return TRUE;
		}

		/* @var $synchronize Techtwo_Mailplus_Model_Product */
		$synchronize = $productHelper->findByProductId($product->getId(), $store_id);

		if ( !$synchronize->getId() )
		{
			$synchronize->clearInstance();
			/* @var $synchronize Techtwo_Mailplus_Model_Product */
			$synchronize = Mage::getModel('mailplus/product');

			$synchronize->setData(array(
				'catalog_product_entity_id' => $product->getId(),
				'store_id'                  => $store_id,
				'price'                     => $product->getFinalPrice(),
				'checksum'                  => ''
			));
			$synchronize->save();
		}

		$data = $this->_createMailPlusProductData( $synchronize, $product, $store_id );
		$synchronize->setData('checksum', crc32(serialize($data)));


		// we use the mailplus 'update=TRUE' option, so we don't care about insert or update
		$response = $this->restPost($client, $this->_restBaseUrl().'/product', json_encode($data));

		// ID_NOTUNIQUE

		if ($response != null) {
			$success = 204 === $response->getStatus();
			$client->log(__METHOD__.'('.$product->getId().')');
	
			if ( !$success )
			{
				$this->_processResponseError($response, $product);
			}
		}

		/**
		 * New product is added/ updated at the MailPlus side, or request is queued
		 * Make a record of that so we don't try and add the same product again!
		 */
		try {
			// ensure the update_at is saved on save
			$synchronize->setDataChanges( TRUE );
			$synchronize->save();
		}
		catch (Exception $ex)
		{
			Mage::logException($ex);
			if ( Mage::getStoreConfigFlag('mailplus/debug/log_enabled') )
			{
				throw $ex;
			}
		}

		return $success;
	}

	/**
	 * Throws exception for all status ( also 200, so only error responses here )
	 *
	 * Known error codes:
	 * ID_NOTUNIQUE      - externalProductId must be unique , happens when you insert an product id which is already inserted
	 * PRODUCT_NOTFOUND  - product not found with externalProductId: %s , happens when you update an not existent product
	 *
	 * @throws Exception
	 * @param Zend_Http_Response $response
	 * * @param Mage_Catalog_Model_Product $product_for_auto_recover
	 */
	protected function _processResponseError( Zend_Http_Response $response, Mage_Catalog_Model_Product $product_for_auto_recover=NULL )
	{
		if ( 400 === $response->getStatus() )
		{
			$content_type =  $response->getHeader('content-type'); // zend always use lower case

			if ( 'application/json' === $content_type )
			{
				$json = json_decode($response->getBody());
				$error_type = (string) $json->errorType;

				Mage::throwException('MailPlus product synchronize '.(string) $json->message .' [ ' . $error_type .' ]');
			}
			else
			{
				Mage::throwException('MailPlus product synchronize unknown error '.$response->getBody());
			}
		}
		else
		{
			Mage::throwException('MailPlus product synchronize unknown error '.$response->getStatus());
		}
	}

	public function deleteProduct( Mage_Catalog_Model_Product $product )
	{
		/* @var $collection Techtwo_Mailplus_Model_Mysql4_Product_Collection */
		$collection = Mage::getModel('mailplus/product')->getCollection();
		$stores = $collection->getAllStoreIdsByProductId( $product->getId() );

		/* @var $appEmulation Mage_Core_Model_App_Emulation */
		$appEmulation = Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = NULL;
		foreach ( $stores as $store_id )
		{
			$first = $appEmulation->startEnvironmentEmulation($store_id);
			if ( NULL === $initialEnvironmentInfo )
			{
				$initialEnvironmentInfo = $first;
			}

			$this->_deleteProduct($product);


			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
		}
		if ( $initialEnvironmentInfo )
			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
	}

	/**
	 * Delete the product from MailPlus
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return bool
	 */
	public function _deleteProduct( Mage_Catalog_Model_Product $product )
	{
		$client = $this->getClientByStore($product->getStoreId());
		if (!$client)
			return TRUE;

		/* @var $synchronize Techtwo_Mailplus_Model_Product */
		$synchronize = Mage::getModel('mailplus/product');
		$synchronize = $synchronize->findByProductId( $product->getId(), $product->getStoreId() );

		$external_id = $synchronize->getExternalId();

		if ( $synchronize->getId() )
		{
			$response = $this->restDelete($client, $this->_restBaseUrl().'/product/'.$external_id, false);

			// 204 ok deleted, 404 you deleted a non-existent product ... which is fine too
			if ( 204 !== $response->getStatus() && 404 !== $response->getStatus() )
			{
				// silently log exceptions when they occur
				try { $this->_processResponseError($response); }
				catch ( Exception $ex ) { Mage::logException( $ex ); }

				// eg. on 500 internal server error or if mailplus is gone ...
				// .. don't remove it in the product synchronized table, because it's not deleted

				return FALSE;
			}

			$client->log( __METHOD__.'('.$product->getId().')' );

			// Now remove it from the synchronized table
			$synchronize->delete();
		}

		return TRUE;
	}

	/**
	 * Creates the product data to send to MailPlus
	 * It still lacks the external ids, so you can batch check the externalIds
	 *
	 * @param Techtwo_Mailplus_Model_Product $mailplus_product
	 * @param Mage_Catalog_Model_Product $product
	 * @param $store_id
	 * @return array
	 */
	protected function _createMailPlusProductData( Techtwo_Mailplus_Model_Product $mailplus_product, Mage_Catalog_Model_Product $product, $store_id )
	{
		$external_id = $mailplus_product->getExternalId();
		$categoryPaths = $this->_findDeepestCategories( $product );

		$brand_attribute = $this->_getBrandAttributeCode();
		
		$product_brand = NULL;
		if ($product->offsetExists($brand_attribute)) {
			$product_brand = $product->getAttributeText($brand_attribute);
			if ( FALSE === $product_brand || NULL === $product_brand ) // not a multiselect ?
				$product_brand = $product->getDataUsingMethod($brand_attribute); // you never know if someone created a getMethod
		}

		/* @var $image Mage_Catalog_Helper_Image */
		$image = Mage::helper('catalog/image');

		$imageUrl = Mage::getUrl('mailplusfe/image/get/id/' . $mailplus_product->getId() . '/f/n');
		$imageLargeUrl = Mage::getUrl('mailplusfe/image/get/id/' . $mailplus_product->getId() . '/f/l');

		$store = $product->getStore();
		$mailplus_sku = $store->getId().'-'.$product->getSku();
		$storeName = $store->getWebsite()->getName() . ' - ' . $store->getGroup()->getName() . ' - ' . $product->getStore()->getName();
	
		$description = strip_tags($product->getShortDescription());
		if ($description === NULL || $description === '') {
			$description = $product->getName();
		}

		$specifications = $this->_getProductSpecifications($product, $store_id);

		$visibility = $product->getDataUsingMethod("visibility");
		$visible = (Mage_Catalog_Model_Product_Status::STATUS_ENABLED == $product->getStatus()) &&
					($visibility != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
		
		$data = array(
			'update' => TRUE,
			'product' => array(
				'externalId'        => $external_id,
				'sku'               => $mailplus_sku,
				'gtin'              => $product->getId(), //, $product->getSku(), || SKU is almost always bigger than the maximum allowed
				'name'              => $product->getName(),
				'description'       => $description,
				'category'          => $categoryPaths? reset($categoryPaths):'',
				'price'             => round($product->getFinalPrice()*100),     // must be in cents
				'oldPrice'          => round($product->getPrice()*100),          // must be in cents
				'link'              => $product->getProductUrl(),
				'brand'             => $product_brand? $product_brand:'',
				'imageUrl'          => $imageUrl,
				'imageLargeUrl'     => $imageLargeUrl,
				//'ratingImageUrl'    => '', // TODO: rating image for the product, probably no longer required as mailplus can generate the images
				'addToCartLink'     => '', // TODO: add to cart url here ( if possible, so simple product without custom options )
				'language'          => $storeName,
				'visible'			=> ($visible ? "true" : "false")
			)
		);

		$productData = &$data['product']; // make an easy short-cut to $data[product] using reference

		if ($specifications) {
			$productData['specifications'] = $specifications;
		}
		

		/* @var $review Mage_Review_Model_Review */
		$review = Mage::getModel('review/review');
		$review->getEntitySummary($product);

		$ratingPercent = (int) $product->getRatingSummary()->getRatingSummary(); // this get you a percent 0 to 100
		$reviewsCount  = (int) $product->getRatingSummary()->getReviewsCount(); // this get you a percent 0 to 100

		// this is a link to a single review
		//$data['reviewLink'] = $review->getReviewUrl();

		// this is the best way to get the reviews url, should someone use a different link, than they should be able to just rewrite the block

		/* @var $block Mage_Review_Block_Helper */
		$block = Mage::app()->getLayout()->createBlock('review/helper');
		$block->setDataUsingMethod('product', $product);
		$productData['reviewLink'] = $block->getReviewsUrl();
		if ( FALSE !== ($pos = strpos($productData['reviewLink'], '?')) )
		{
			$productData['reviewLink'] = substr($productData['reviewLink'], 0, $pos);
		}

		if ( $reviewsCount > 0 )
		{
			$mailplusBase = 5;
			$productData['ratingValue'] = $mailplusBase *( $ratingPercent/100 );
		}

		// MailPlus does not want the oldPrice is the price==oldPrice
		if ($data['product']['price'] === $data['product']['oldPrice'])
		{
			unset($data['product']['oldPrice']);
		}

		return $data;
	}

	private function _getProductSpecifications($product, $store_id) {
		$specsGroupName = Mage::getStoreConfig('mailplus/syncsettings/productspecs', $store_id);
		if ($specsGroupName == NULL) {
			return NULL;	
		}

		$setId = $product->getAttributeSetId();

		$groups = Mage::getModel('eav/entity_attribute_group')
			->getCollection()
			->addFieldToFilter('attribute_set_id', $setId)
			->load();

		$result = array();

		foreach($groups as $group) {
			if ($specsGroupName === $group->getAttributeGroupName()) {
				$productAttrs = $product->getAttributes();
				foreach($productAttrs as $attrName => $attr) {
					$groupId = $attr->getAttributeGroupId();
					if ($groupId == $group->getId()) {
						$labels = $attr->getStoreLabels();
						$label = NULL;
						if (array_key_exists($product->getStoreId(), $labels)) {
							$label = $labels[$product->getStoreId()];
						}
						if ($label == null || $label == '') {
							$label = $attr->getStoreLabel();
						}

						if ($label != null && $label != '') {	
							$value = NULL;
							if ($attr->usesSource()) {
								$value = $product->getResource()->getAttribute($attrName)->getSource()->getOptionText($product->getData($attrName));
							} else {
								$value = $product->getResource()->getAttribute($attrName)->getFrontEnd()->getValue($product);
							}
								
							if ($value != NULL || $value != '') {
								$specification = array(
									'description' => $label,
									'value' => strip_tags($value),
									'rank' => $attr->getSortOrder()
								);
								$result[] = $specification;
							}
						}
					}
				}
			}
		}

		$numItems = count($result); 
		if ($numItems == 0)
			return NULL;

		usort($result, array("Techtwo_Mailplus_Helper_Rest", "specCompare")); 		

		if ($numItems <= 5) {
			return $result;
		}
		
		return array_slice($result, 0, 5);
	}

	static function specCompare($a, $b) {
		return ($a['rank'] > $b['rank'] ? +1 :  -1);
	} 
	

	public function getModifiedContacts( $websiteId, $fromDate, $toDate )
	{
		$client = $this->getClientByWebsite($websiteId);
		if ( !$client )
			return array();

		$fromDate = date(DateTime::ATOM, $fromDate);
		$toDate   = date(DateTime::ATOM, $toDate);

		$response = $client->restGet($this->_restBaseUrl().'/contact/updates/list', array('fromDate'=>$fromDate, 'toDate'=>$toDate));
		$client->log(__METHOD__."( '$fromDate', '$toDate'' )");

		if ( !$response->isSuccessful() )
			return FALSE;

		return json_decode( $response->getBody() );
	}

	public function getBounces( $websiteId, $fromDate, $toDate )
	{
		$client = $this->getClientByWebsite($websiteId);
		if ( !$client )
			return array();
		
		$fromDate = date(DateTime::ATOM, $fromDate);
		$toDate   = date(DateTime::ATOM, $toDate);
		

		$response = $client->restGet($this->_restBaseUrl().'/contact/bounces/list', array('fromDate'=>$fromDate, 'toDate'=>$toDate));
		$client->log(__METHOD__."( '$fromDate', '$toDate' )");

		if ( !$response->isSuccessful() )
			return FALSE;

		return json_decode( $response->getBody() );
	}

	public function getTriggers( $client )
	{
		$cache_id = 'techtwo_mailplus_campaign_triggers';


		//Mage::app()->getWebsite($websiteId)->getConfig('web/unsecure/base_url')
		$store = Mage::app()->getFrontController()->getRequest()->getParam('store');
		$website = Mage::app()->getFrontController()->getRequest()->getParam('website');

		if ($store)
			$cache_id .= '-s-'.$store;

		if ($website)
			$cache_id .= '-w-'.$website;


		/* @var $cache Varien_Cache_Core */
		$cache = Mage::app()->getCache();
		$cache_data = $cache->load($cache_id);
		if ( $cache_data )
		{
			$cache_data = unserialize($cache_data);
			if ( $cache_data ) // might be a unserialize problem
				return $cache_data;
		}

		/* @var $helper Techtwo_Mailplus_Helper_Rest */
		$helper = Mage::helper('mailplus/rest');

		$aux = FALSE;

		if ( $client )
		{
			$response = $client->restGet($this->_restBaseUrl().'/campaign/list');
			if ( $response->isSuccessful() )
				$aux = array();

			$client->log(__METHOD__.'()');

			$content_type =  $response->getHeader('content-type'); // zend always use lower case

			
			$json = json_decode($response->getBody());


			if (!$response->isSuccessful())
			{
				$error_type = (string) $json->errorType;
				Mage::logException(new Exception('MailPlus campaign list '.(string) $json->message .' [ ' . $error_type .' ]'));
				return FALSE;
			}

			foreach ($json as $c)
			{
				if ( $c->active )
				{
					/* @var $campaign Techtwo_Mailplus_Model_System_Campaign */
					$campaign = Mage::getModel('mailplus/system_campaign');
					$campaign->setName( (string) $c->name );
					$campaign->setEncryptedId( (string) $c->encryptedId );
					if ( isset($c->triggers) )
					{
						foreach ($c->triggers as $trigger)
						{
							$campaign->addTrigger((string)$trigger->encryptedId, (string)$trigger->name);
						}
					}

					$aux[$c->encryptedId] = $campaign;
				}
			}
		}
		else
		{
			$cache->remove($cache_id);
			return FALSE;
		}

		Mage::app()->getCache()->save( serialize($aux), $cache_id, array(Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Attribute_Collection::CACHE_TAG), 900 );

		return $aux;
	}

	/**
	 * Get the branch attribute
	 *
	 * By default the branch attribute is called 'manufacturer' in Magento.
	 *
	 * @todo create setting a somewhere
	 * @return string
	 */
	protected function _getBrandAttributeCode()
	{
		return 'manufacturer';
	}

	/**
	 * Finds the deepest paths for a given product
	 *
	 * a book in listed in eg. category book, book/fantasy and /featured
	 * will correctly result in: /book/fantasy and in /featured
	 * it checks for the deepest and unique category paths of the product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return array
	 */
	protected function _findDeepestCategories( Mage_Catalog_Model_Product $product )
	{
		// Set the categories, multiple categories are joined on '/' as delimeter
		$categories = array();
		$categoriesCollection = $product->getCategoryCollection();

		$categoriesCollection
			->addAttributeToSelect(array('name'))
			->setOrder('level','DESC');

		$deepestNest = array();

		$categoryPaths = Array();
		foreach ($categoriesCollection as $category )
		{ /* @var $category Mage_Catalog_Model_Category */
			$deepestNestFound = TRUE;
			foreach ($deepestNest as $nest)
			{
				if ( 0 === strpos($nest, $category->getPath()) )
				{
					$deepestNestFound = FALSE;
				}
			}

			if ($deepestNestFound)
			{
				$deepestNest []= $category->getPath();
			}

			$categories[$category->getId()] = $category->getName();

			$parentCats = $category->getParentCategories();
			$pathNames = Array();
			foreach($parentCats as $pCat) {
				$pathNames[] = $pCat->getName();
			}
			$categoryPaths[$category->getPath()] = implode("/", $pathNames);
		}

		$result = Array();
		foreach($categoryPaths as $key => $value) {
			if (in_array($key, $deepestNest)) {
				$result[$key] = $value;
			}
		}
		
		return $result;
	}

	/**
	 * @param string $externalId The externalId, this is {store_id}-{entity_id}
	 * @return array|NULL Array of mailplus data or NULL on not found
	 * @throws Techtwo_Mailplus_Client_Exception
	 */
	public function getContactByExternalId( $externalId, $storeId )
	{
		$client = $this->getClientByStore($storeId);
	
		if ($client === NULL) {
			return NULL;
		}
	
		$response = $client->restGet($this->_restBaseUrl().'/contact/'.$externalId);
		$client->log(__METHOD__."( $externalId )");
		$error = $client->getError();

		if ( NULL !== $error )
		{
			$exception = new Techtwo_Mailplus_Client_Exception( $error->getMessage()."[{$error->getType()}]", $error->getCode() );
			$exception->setType( $error->getType() );

			if ('CONTACT_NOT_FOUND' === $error->getType())
				return NULL;
			throw $exception;
		}

		return json_decode( $response->getBody() );
	}

	/**
	 * * Options
	 * purge - Clears contact data on TRUE. On FALSE the new data is merged/added to the old data.
	 *
	 * @param $contact_data
	 * @param array $options
	 * @return bool
	 * @throws Techtwo_Mailplus_Client_Exception
	 */
	public function updateContact( $storeId, $contact_data, array $options=array() )
	{
		/* @var $configHelper Techtwo_Mailplus_Helper_Config */
		$configHelper = Mage::helper('mailplus/config');
		
		if (!$configHelper->syncActiveForStore($storeId) || !$configHelper->contactSyncAllowedForStore($storeId)) {
			return TRUE;
		}
			
		$options = array_merge( array(
			'purge' => FALSE,
		), $options);

		$externalId = $contact_data['externalId'];

		$data = array(
			'purge'   => (bool) $options['purge'],
			'contact' => $contact_data
		);

		$client = $this->getClientByStore( $storeId );
		if (!$client) {
			return true;
		}
		
		$response = $this->restPut($client, $this->_restBaseUrl().'/contact/'.$externalId, json_encode($data) );
		$client->log(__METHOD__."( $externalId )");
		$error = $client->getError();
		if ( NULL !== $error )
		{
			$exception = new Techtwo_Mailplus_Client_Exception( $error->getMessage()."[{$error->getType()}]", $error->getCode() );
			$exception->setType($error->getType());
			throw $exception;
		}

		return TRUE;
	}

	/**
	 * Either create or update an contact
	 *
	 * options:
	 * - update : default TRUE
	 * - purge  : default FALSE
	 *
	 * @param $contact_data
	 * @param array $options
	 * @return bool
	 * @throws Exception
	 */
	public function saveContact( $websiteId, $storeId, $contact_data, array $options=array() )
	{
		/* @var $dataHelper Techtwo_Mailplus_Helper_Data */
		$dataHelper = Mage::helper('mailplus');
		/* @var $configHelper Techtwo_Mailplus_Helper_Config */
		$configHelper = Mage::helper('mailplus/config');
	
		if ($storeId != 0 && (!$configHelper->syncActiveForStore($storeId) || !$configHelper->contactSyncAllowedForStore($storeId))) {
			return $this;
		} else if (!$configHelper->syncActiveForSite($websiteId) || !$configHelper->contactSyncAllowedForSite($websiteId)) {
			return $this;
		}

		$options = array_merge( array(
			'update' => TRUE,
			'purge'  => FALSE,
		), $options);

		$data = array(
			'purge'   => (bool) $options['purge'],
			'update'  => (bool) $options['update'],
			'contact' => $contact_data
		);

		$client = $this->getClientByWebsite($websiteId);
		if ( $client )
		{
			$response = $this->restPost($client, $this->_restBaseUrl().'/contact/', json_encode($data) );
			$client->log(__METHOD__."( externalId : {$contact_data['externalId']} )");
			
			$error = $client->getError();
			if ( NULL !== $error )
				throw new Exception( $error->getMessage()."[{$error->getType()}]", $error->getCode() );
		}

		return TRUE;
	}

	/**
	 * Checks if $value_to_check is a valid constant value by constant name prefix
	 *
	 * @param $constant_prefix
	 * @param $value_to_check
	 * @param null $constant_name
	 * @return bool
	 */
	protected function _validateConstant($constant_prefix, $value_to_check, &$constant_name=NULL)
	{
		$reflection = new ReflectionClass($this);
		$constants = $reflection->getConstants();
		foreach ( $constants as $constant_name => $value )
		{
			if ( 0 === strpos($constant_name, $constant_prefix) )
			{
				if ( $value === $value_to_check )
				{
					return TRUE;
				}
			}
		}
		$constant_name = NULL;
		return FALSE;
	}

	public function getAllPermissionBits()
	{
		$aux = array();

		$reflection = new ReflectionClass($this);
		$constants = $reflection->getConstants();
		foreach ( $constants as $constant_name => $value )
		{
			if ( 0 === strpos($constant_name, 'PERMISSION_BIT_') )
			{
				$aux[$constant_name] = $value;
			}
		}
		return $aux;
	}

	/**
	 * Update a permission
	 *
	 * @param string $externalId The external contact id
	 * @param int $permission
	 * @param bool $allowed
	 * @return bool
	 */
	public function updateContactPermission($storeId, $externalId, $permission, $allowed )
	{
		if ( !$this->_validateConstant('PERMISSION_BIT_', $permission) )
			Mage::throwException('Incorrect permission, see PERMISSION_BIT_ constants');

		// now create a small user data
		$data = array(
			'externalId' => $externalId,
			'properties' => array(
				'permissions' => array(
						array(
							'bit' => $permission, 
							'enabled' => $allowed
					)
				)
			)
		);

		// update the contact with the new data
		return $this->updateContact($storeId, $data);
	}


	/**
	 * Disables all permissions of the contact in MailPlus
	 *
	 * @param $externalId
	 * @return bool
	 */
	public function disableContact($storeId, $externalId )
	{
		$permissionsData = array();
		$permissionsData[] = array(
			'bit'     => self::PERMISSION_BIT_NEWSLETTER,
			'enabled' => FALSE
		);

		// now create a small user data
		$data = array(
			'externalId' => $externalId,
			'properties' => array(
				'permissions' => $permissionsData
			)
		);

		// update the contact with the new data
		return $this->updateContact($storeId, $data);
	}

	public function clearContactPropertiesCache($websiteId)
	{
		$cache_id = __CLASS__.'_getContactProperties_' . $websiteId;
		/* @var $cache Varien_Cache_Core */
		$cache = Mage::app()->getCache();
		if ( $cache->load($cache_id) )
			$cache->remove($cache_id);
		return $this;
	}

	/**
	 * Retrieve all contact properties
	 *
	 * Contact properties is cached, you can remove it with {@link clearContactPropertiesCache()}
	 *
	 * @return bool|mixed
	 */
	public function getContactProperties($websiteId)
	{
		$cache_id = __CLASS__.'_getContactProperties_' . $websiteId;
		/* @var $cache Varien_Cache_Core */
		$cache = Mage::app()->getCache();
		$cache_data = $cache->load($cache_id);
		if ( $cache_data ) {
			$cache_data = unserialize($cache_data);
			if ( $cache_data ) {// might be a unserialize problem
				return $cache_data;
			}
		}

		$client = $this->getClientByWebsite($websiteId);
		if ( NULL === $client ) {
			//Mage::log('MailPlus was not activated, getContactProperties is off', Zend_Log::ERR);
			return FALSE;
		}

		try {
			$response = $client->restGet($this->_restBaseUrl().'/contact/properties/list');
		} catch (Exception $e) {
			Mage::logException($e);
			return FALSE;	
		}
		
		$client->log(__METHOD__.'()');
		$error = $client->getError();

		if ( $error ) {
			$exception = new Exception($error->getMessage()." [{$error->getType()}]", $error->getCode());
			Mage::logException( $exception );
			return FALSE;
		}

		$rawProperties = json_decode($response->getBody());

		$aux = array();
		foreach ( $rawProperties as $p ) {
			$property = new Techtwo_Mailplus_Client_Contact_Property;
			$property
				->setName( $p->name )
				->setDescription( $p->description )
				->setType( $p->type );

			$aux[(string)$p->name] = $property;
		}


		Mage::app()->getCache()->save( serialize($aux), $cache_id, array(Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Attribute_Collection::CACHE_TAG), 900 );

		return $aux;
	}

	/**
	 * Get or create a mailplus/user from the given order.
	 * 
	 * @param Mage_Sales_Model_Entity_Order $order
	 */
	public function getUserFromOrder($order) {
		$dataHelper = Mage::helper('mailplus');
		$user = Mage::getModel('mailplus/user');
		
		if ($order->getCustomerId()) {
			$user->loadByCustomerId($order->getCustomerId());
			if (!$user->getId()) {
				$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
				$user = $dataHelper->createUserFromCustomer($customer);
			}
		} else if ($order->getCustomerGroupId() == 0) {
			// Order done by guest account. Check if e-mail exists and if not, create user.
			$email = $order->getCustomerEmail();
			$firstname = $order->getCustomerFirstname();
			$lastname = $order->getCustomerLastname();
			
			if ($email) {
				$existingUser = $user->findByEmail($email);
				
				if ($email && $existingUser->getId()) {
					$user = $existingUser;
				} else {
					$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
					if ($subscriber && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
						$user->setPermission( Techtwo_Mailplus_Helper_Rest::PERMISSION_BIT_NEWSLETTER, TRUE );
					}
					$user->setEnabled(TRUE);
					$user->setCreatets( time() );
				}
				
				// update the user.
				$user->setEmail($email);
				$user->setFirstname($firstname);
				$user->setLastname($lastname);
				$user->setStoreId( $order->getStoreId());
			}				
		}
		
		return $user;
	}
	
	
	/**
	 * Sync the order to MailPlus.
	 * When $checkSyncqueue is FALSE, the order will always by synced to MailPlus.
	 * When $checkSyncqueue is TRUE, the order will only by synced to MailPlus when
	 * the Syncqueue is empty, else it will be saved to the Syncqueue instead
	 *  
	 * @param Mage_Sales_Model_Entity_Order $order
	 * @param boolean $checkSyncqueue
	 */
	public function saveOrder($order, $checkSyncqueue) {
		$dataHelper = Mage::helper('mailplus');
		
		/* @var $configHelper Techtwo_Mailplus_Helper_Config */
		$configHelper = Mage::helper('mailplus/config');
		
		if (!$configHelper->contactSyncAllowedForStore($order->getStoreId())) {
			return;
		}
		
		$client = $this->getClientByStore($order->getStoreId());
		if ($client == NULL) {
			return;
		}

		if ($checkSyncqueue) {
			$websiteId = Mage::getModel('core/store')->load($order->getStoreId())->getWebsiteId();
			if ($dataHelper->getSyncCount($websiteId, Techtwo_Mailplus_Model_Syncqueue::TYPE_ORDER) > 0) {
				/*
				 * Do not save an order when there are still items to be synced but
				 * save it to the queue instead
				 */ 
				
				$dataHelper->saveSyncItem($websiteId, $order->getId(), Techtwo_Mailplus_Model_Syncqueue::TYPE_ORDER);
				return;
			}
		}
		
		$orderData = array();
		$orderData['externalId'] = $order->getId();
		$orderData['date'] = date(DateTime::ATOM, strtotime($order->getCreatedAt()));
	
		$user = $this->getUserFromOrder($order);
		if ($user === NULL) {
			return;
		}
		
		// Save if its a new user
		if (!$user->getId()) {
			try {
				$user->save();
			}
			catch( Exception $e ) {
				Mage::logException($e);
				return null;
		
			}
		}
		
		$orderData['externalContactId'] = $user->getId();

		$product_ids = array();
		foreach ( $order->getAllVisibleItems() as $item ) {
			/* @var $mailplus_product Techtwo_Mailplus_Model_Product */
			$mailplusProduct = Mage::getModel('mailplus/product');

			if ($item && $item->getProductId()) {
				// Do not add products that are cancelled or refunded
				if ($item->getQtyOrdered() != $item->getQtyRefunded() && 
						($item->getQtyOrdered() != $item->getQtyCanceled())) {
					$product = $mailplusProduct->findByProductId($item->getProductId(), $order->getStoreId());
					
					if (!$product->getId() ) {
						$orderProduct = Mage::getModel('catalog/product')->load($item->getProductId());
						if ($orderProduct && $orderProduct->getId()) {
							$this->saveProduct($orderProduct);
							$product = $mailplusProduct->findByProductId($item->getProductId());
						}					
					}
			
					if ($product->getId()) {
						$product_ids[]= $product->getExternalId();
				}
				}
			}
		}

		if (count($product_ids) > 0) {
			$orderData['externalProductIds'] = $product_ids;
			
			$refunded = $order->getTotalRefunded();
			if (!$refunded) {
				$refunded = 0;
			}
			
			$orderData['value'] = round(($order->getGrandTotal() - $refunded) * 100); // MailPlus wants cents
	
			$data = array();
			$data['update'] = true;
			$data['order'] = $orderData;
	
			$response = NULL;
			try {
				$response = $this->restPost($client, $this->_restBaseUrl().'/order/', json_encode($data) );
			}
			catch (Exception $exception) {
				Mage::logException($exception);
			}
	
			if ($response !== NULL) {
				$error = $client->getError();
				if ( NULL !== $error )
					throw new Exception( $error->getMessage()."[{$error->getType()}]", $error->getCode());
			}
		}
	}

	public function deleteOrder($order) {
		$dataHelper = Mage::helper('mailplus');
		$client = $this->getClientByStore($order->getStoreId());

		if ($client == NULL) {
			return;
		}

		$response = NULL;
		try {
			$response = $this->restDelete($client, $this->_restBaseUrl().'/order/' . $order->getId());
		}
		catch (Exception $exception) {
			Mage::logException($exception);
		}

		if ($response !== NULL) {
			$error = $client->getError();
			if ( NULL !== $error )
				Mage::logException(new Exception( $error->getMessage()."[{$error->getType()}]", $error->getCode()));
		}
	}

	
	public function restDelete($client, $url, $queueOnError = true) {
		$response = NULL;
		try {
			$response = $client->restDelete($url);
			if (!$queueOnError) {
				return $response;
			}
			$this->_checkAndQueueCall("POST", $url, NULL, $response, $client->getSiteId());
		}
		catch (Exception $e) {
			// rethrow when not queueing
			if (!$queueOnError) {
				throw $e;
			}
			$this->queueCall("POST", $url, NULL, $response, $e, $client->getSiteId());
		}
		
		return $response;
	}
	
	public function restPost($client, $url, $data, $queueOnError = true) {
		$response = NULL;
		try {
			$response = $client->restPost($url, $data);
			if (!$queueOnError) {
				return $response;
			}
				
			$this->_checkAndQueueCall("POST", $url, $data, $response, $client->getSiteId());
		}
		catch (Exception $e) {
			// rethrow when not queueing
			if (!$queueOnError) {
				throw $e;
			}
			
			$this->_queueCall("POST", $url, $data, $response, $e, $client->getSiteId());
		}
		
		return $response;
	}
	
	public function restPut($client, $url, $data, $queueOnError = true) {
		$response = NULL;
		try {
			$response = $client->restPut($url, $data);
			if (!$queueOnError) {
				return $response;
			}
	
			$this->_checkAndQueueCall("PUT", $url, $data, $response, $client->getSiteId());
		}
		catch (Exception $e) {
			// rethrow when not queueing
			if (!$queueOnError) {
				throw $e;
			}
			$this->_queueCall("PUT", $url, $data, $response, $e, $client->getSiteId());
		}
		
		return $response;
	}
	
	protected function _shouldRetry($response) {
		if ($response == null) {
			return TRUE;
		}
	
		$status = $response->getStatus();
		// All 200 codes are OK.	
		if ($status >= 200 && $status < 300) {
			return FALSE;
		}
	
		/*
		 * MailPlus gives the following status codes on error:
		* 400: Bad request. A given value was invalid (e.g. wrong e-mail adres for a contact)
		* 401: Unauthorized. Wrong credentials (key and secret) given
		* 404: Not found. The given resource does not exists (e.g. when doing an update on a contact which does not exists in MailPlus)
		*
		* These should not be retried
		*/
	
		if ($status == 400 || $status == 401 || $status == 404) {
			return FALSE;
		}
	
		return TRUE;
	}
	
	protected function _checkAndQueueCall($method, $url, $data, $response, $websiteId) {
		if ($this->_shouldRetry($response)) {
			$this->_queueCall($method, $url, $data, $response, NULL, $websiteId);
		}
	}
	
	protected function _queueCall($method, $origUrl, $data, $response, $exception, $websiteId) {
		$url = substr($origUrl, strlen($this->_restBaseUrl()));
		
		$queueItem = Mage::getModel('mailplus/restqueue');
		$queueItem->setMethod($method);
		$queueItem->setUrl($url);
		$queueItem->setPayload($data);
		$queueItem->setTries(1);
	
		if ($response != null) {
			$queueItem->setLastError($response->getStatus());
			$queueItem->setLastResponse($response->getBody());
		} else if ($exception != null) {
			$queueItem->setLastError($exception->getMessage());
		}
		
		$date = time();
		$queueItem->setCreatedAt($date);
		$queueItem->setLastRunAt($date);
		$queueItem->setSite($websiteId);
		
		$date = $date + 5 * 60; // Add 5 minutes
		$queueItem->setNextRunAt($date);
		
		$queueItem->save();
	}
	
	public function handleQueueItem($item) {
		$method = $item->getMethod();

		$client = $this->getClientByWebsite($item->getSite());
		if ($client == null) {
			return;
		}
		$response = null;
		$exception = null;
		
		try {
			switch ($method) {
				case 'POST':
					$response = $this->restPost($client, $this->_restBaseUrl() . $item->getUrl(), $item->getPayload(), false);
					break;
				case 'PUT':
					$response = $this->restPut($client, $this->_restBaseUrl() . $item->getUrl(), $item->getPayload(), false);
					break;
				case 'DELETE':
					$response = $this->restDelete($client, $this->_restBaseUrl() . $item->getUrl(), false);
					break;
			}
		}
		catch (Exception $e) {
			Mage::logException($e);
			$exception = $e;
		}
		
		if ($this->_shouldRetry($response)) {
			$this->_reQueueItem($item, $response, $exception);
		} else {
			$item->delete();
		}
	}
	
	public function _reQueueItem($item, $response, $exception) {
		$date = time();
		$tries = $item->getTries() + 1;
		
		if ($tries > 6) {
			$item->delete();
			Mage::log('Removed restqueue item ' . $item->getId() . ' after 6 tries.');
			return;
		}
		
		if ($response != null) {
			$item->setLastError($response->getStatus());
			$item->setLastResponse($response->getBody());
		} else if ($exception != null) {
			$item->setLastError($exception->getMessage());
		}
		
		$item->setTries($tries);
		$item->setLastRunAt($date);
		$item->setNextRunAt($date + pow(2, $tries) * 5 * 60   )  ; // data = date + 2^tries x 5 minutes 
			
		$item->save();
	}
}
