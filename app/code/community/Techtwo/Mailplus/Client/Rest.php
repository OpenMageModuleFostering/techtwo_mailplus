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
class Techtwo_Mailplus_Client_Rest extends Zend_Rest_Client
{
	const REST_LOG_FILE = 'mailplus_rest.log';

	private $_siteId = null;
	
	/**
	 * Create a log of the request and response call
	 *
	 * @param null $title
	 * @return Techtwo_Mailplus_Client_Rest
	 */
	public function log($title=NULL)
	{
		if ( Mage::getStoreConfigFlag('mailplus/debug/log_enabled') )
		{
			$log = '';
			if ($title)
			{
				$log .= $title.PHP_EOL;
				$log .= '--------------------------'.PHP_EOL;
			}
			$log .= 'REQUEST: '.PHP_EOL.PHP_EOL;
			$log .= $this->getHttpClient()->getLastRequest();
			$log .= PHP_EOL.'--------------------------'.PHP_EOL;
			$log .= 'RESPONSE: '.PHP_EOL;
			$log .= print_r($this->getHttpClient()->getLastResponse(), TRUE).PHP_EOL;

			Mage::log($log, Zend_Log::DEBUG, self::REST_LOG_FILE);
		}

		return $this;
	}

	/**
	 * Assumes all status codes 200 are valid
	 *
	 * @return NULL|Techtwo_Mailplus_Client_Error
	 */
	public function getError()
	{
		$response = $this->getHttpClient()->getLastResponse();
		if ($response == NULL) {
			return NULL;
		} 
			
		
		$status = $response->getStatus();
		if ( $response->isSuccessful() /*$status >= 200 && $status < 299*/ )
			return NULL;

		$content_type =  $response->getHeader('content-type'); // zend always use lower case

		$error = new Techtwo_Mailplus_Client_Error();
		$error->setCode($status);

		if ( 'application/xml' === $content_type )
		{
			$xml = simplexml_load_string($response->getBody());
			$error
				->setMessage( (string) $xml->message )
				->setType( (string) $xml->errorType );
		}
		elseif ( 'application/json' === $content_type )
		{
			$json = json_decode($response->getBody());
			$error
				->setMessage( (string) $json->message )
				->setType( (string) $json->errorType );
		}
		else
		{
			$error
				->setMessage( 'Unknown error occurred' )
				->setType( NULL );
		}

		if ( '' == $error->getMessage() )
			$error->setMessage( 'Unknown error occurred' );

		return $error;
	}
	
	public function setSiteId($siteId) {
		$this->_siteId = $siteId;
	}
	
	public function getSiteId() {
		return $this->_siteId;
	}
	
}