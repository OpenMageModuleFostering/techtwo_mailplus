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
class Mailplus_Mailplus_Entry
{
	/**
	 * Array of Feed data for rendering by Extension's renderers
	 *
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * Encoding of all text values
	 *
	 * @var string
	 */
	protected $_encoding = 'UTF-8';
	
	/**
	 * Set feed encoding
	 * 
	 * @param  string $enc 
	 * @return Mailplus_Mailplus_Renderer_Entry
	 */
	public function setEncoding($enc)
	{
			$this->_encoding = $enc;
			return $this;
	}
	
	/**
	 * Get feed encoding
	 * 
	 * @return string
	 */
	public function getEncoding()
	{
			return $this->_encoding;
	}
	
	/**
	 * Set price
	 * 
	 * @param  string $value 
	 * @return Mailplus_Mailplus_Renderer_Entry
	 */
	public function setMailplusPrice( Mage_Catalog_Model_Product $product )
	{
		// GET prijs exclusive BTW
		$taxHelper  = Mage::helper('tax');
		/* @var $taxHelper Mage_Tax_Helper_Data */
		
		$price = $taxHelper->getPrice( $product, $product->getFinalPrice(), true );
		$price_no_btw = $taxHelper->getPrice( $product, $product->getFinalPrice() );
		
		$this->_data['price'] = array(
			'product_price' => $taxHelper->getPrice( $product, $product->getPrice(), true ),
			'product_without_taxes' => $taxHelper->getPrice( $product, $product->getPrice() ),
			'final' => $price,
			'without_taxes' => $price_no_btw
		);
		return $this;
	}
		
		/**
	 * Set special price
	 * 
	 * @param  string $value 
	 * @return Mailplus_Mailplus_Renderer_Entry
	 */
	public function setMailplusSpecialPrice( Mage_Catalog_Model_Product $product )
	{
		// GET prijs exclusive BTW
		$taxHelper  = Mage::helper('tax');
		/* @var $taxHelper Mage_Tax_Helper_Data */
		//var_dump($product); die();
		
		$price = $taxHelper->getPrice( $product, $product->getSpecialPrice(), true );
		$price_no_btw = $taxHelper->getPrice( $product, $product->getSpecialPrice(), false );
		
		$this->_data['specialprice'] = array(
			'final' => $price,
			'without_taxes' => $price_no_btw
		);
		return $this;
	}
		
	public function setMailplusWeight($value)
	{
		if (is_numeric($value))
			$this->_data['weight'] = (float) $value; // casting to float ensures 0 values will not be printed
		return $this;
	}
		
	/**
	 * @param type $base_image
	 * @param type $small_image  This is mailplus default 'image'
	 * @param type $thumbnail
	 * @return \Mailplus_Mailplus_Entry 
	 */
	public function setMailplusImage($base_image, $small_image, $thumbnail)
	{
		$this->_data['image'] = array(
				'base' => $base_image,
				'small' => $small_image,
				'thumbnail' => $thumbnail
		);
		return $this;
	}
	
	public function setMailplusSku($value)
	{
		$this->_data['sku'] = $value;
		return $this;
	}

	public function setMailplusLogo($value)
	{
		$this->_data['logo'] = $value;
		return $this;
	}
	
	public function setMailplusQty($value)
	{
		$this->_data['qty'] = $value;
		return $this;
	}
	
	public function setMailplusBackorders($value)
	{
		$this->_data['backorders'] = $value;
		return $this;
	}
	
	public function setMailplusIsInStock($value)
	{
		if ( is_bool($value) )
			; // bool is ok
		elseif ( is_string($value) && ( '1' === $value || '0' === $value ) )
			$value = '1' === $value; // a string either "1" or "0" is good, but we replace it boolean
		else
			throw new Zend_Feed_Exception('invalid parameter: MailPlus "IsInStock" must be an boolean or a string "1" or "0"');
		
		$this->_data['isInStock'] = $value;
		return $this;
	}
	
	public function setMailPlusShortDescription($value)
	{
		$this->_data['shortDescription'] = $value;
	}
		
	/*
	public function setMailplusSummary($value)
	{
		if (iconv_strlen($value, $this->getEncoding()) > 4000) {
			#require_once 'Zend/Feed/Exception.php';
			throw new Zend_Feed_Exception('invalid parameter: "summary" may only'
			. ' contain a maximum of 4000 characters');
		}
		$this->_data['summary'] = $value;
		return $this;
	}
	*/
	
	/**
	 * Overloading to mailplus specific setters
	 * 
	 * @param  string $method 
	 * @param  array $params 
	 * @return mixed
	 */
	public function __call($method, array $params)
	{
		if (strlen($method) < 9)
			return;

		$point = Zend_Feed_Writer::lcfirst(substr($method, 11));

		if ( !method_exists($this, 'setMailplus' . ucfirst($point)) && !method_exists($this, 'addMailplus' . ucfirst($point)) )
		{
			#require_once 'Zend/Feed/Writer/Exception/InvalidMethodException.php';
			throw new Zend_Feed_Writer_Exception_InvalidMethodException(
				'invalid method: ' . $method
			);
		}

		if (!array_key_exists($point, $this->_data)  || empty($this->_data[$point]) )
		{
			return null;
		}

		return $this->_data[$point];
	}
}

