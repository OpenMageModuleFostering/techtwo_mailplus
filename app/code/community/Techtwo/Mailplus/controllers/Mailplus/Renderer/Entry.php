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
class Mailplus_Mailplus_Renderer_Entry extends Zend_Feed_Writer_Extension_RendererAbstract
{
	/**
	* Set to TRUE if a rendering method actually renders something. This
	* is used to prevent premature appending of a XML namespace declaration
	* until an element which requires it is actually appended.
	*
	* @var bool
	*/
	protected $_called = false;

	protected $_dataContainer;

	/**
	* Render entry
	* 
	* @return void
	*/
	public function render()
	{
		$this->_dataContainer = $this->getDataContainer();

		$this->_setPrice($this->_dom, $this->_base);
		$this->_setSpecialPrice($this->_dom, $this->_base);
		$this->_setImage($this->_dom, $this->_base);
		$this->_setSku($this->_dom, $this->_base);
		$this->_setQty($this->_dom, $this->_base);
		$this->_setBackorders($this->_dom, $this->_base);
		$this->_setIsInStock($this->_dom, $this->_base);
		$this->_setShortDescription($this->_dom, $this->_base);
		$this->_setWeight($this->_dom, $this->_base);
		$this->_setLogo($this->_dom, $this->_base);


		if ($this->_called)
			$this->_appendNamespaces();
	}

	/**
	* Append namespaces to entry root
	* 
	* @return void
	*/
	protected function _appendNamespaces()
	{
		$this->getRootElement()->setAttribute('xmlns:mailplus', 'http://api.mailplus.nl/rss/mailplus/');
	}

	private function _priceElementHelper(DOMDocument $dom, DOMElement $root, $price, $isOldPrice, $isExlTaxes)
	{
		$prefix = $isOldPrice? 'oude':'';
		$prefix .= 'prijs';
		$prefix .= $isExlTaxes? 'exbtw':'';

		list( $integer, $decimals ) = strpos((string) $price, '.')? explode('.', ''.$price):array($price, '00');

		if ( strlen($decimals) > 2)
			$decimals = substr($decimals, 0,2);
		else
		{
			while ( strlen($decimals) < 2 )
				$decimals .= '0';
		}

		$el = $dom->createElement("mailplus:{$prefix}");
		$text = $dom->createTextNode( "{$integer}.{$decimals}" );
		$el->appendChild( $text );
		$root->appendChild( $el );

		$el = $dom->createElement("mailplus:{$prefix}voorkomma");
		$text = $dom->createTextNode( $integer );
		$el->appendChild( $text );
		$root->appendChild( $el );

		$el = $dom->createElement("mailplus:{$prefix}nakomma");
		$text = $dom->createTextNode( $decimals );
		$el->appendChild( $text );
		$root->appendChild( $el );

	}


	/**
	* If special price is set, then mailplus 'special price' is set to 'prijs' instead. The value 'price' itself is moved to 'oudeprijs'.
	* @param DOMDocument $dom
	* @param DOMElement $root
	* @return type 
	*/
	protected function _setPrice(DOMDocument $dom, DOMElement $root)
	{
		$priceSet = $this->_dataContainer->getMailplusPrice();
		if (!$priceSet)
			return;

		$oldPriceSet = NULL;
		$specialPriceSet = $this->_dataContainer->getMailplusSpecialprice();
		if ( $specialPriceSet )
		{
			// Special price becomes price
			// Price becomes old
			$oldPriceSet = array(
				'final' => $priceSet['product_price'],
				'without_taxes' => $priceSet['product_without_taxes'],
			);
			$priceSet = $specialPriceSet;
		}

		$this->_priceElementHelper($dom, $root, $priceSet['final'], false, false);
		$this->_priceElementHelper($dom, $root, $priceSet['without_taxes'], false, true);

		if ( $specialPriceSet )
		{
			$this->_priceElementHelper($dom, $root, $oldPriceSet['final'], true, false);
			$this->_priceElementHelper($dom, $root, $oldPriceSet['without_taxes'], true, true);
		}



		$this->_called = true;
	}

	protected function _setSpecialPrice(DOMDocument $dom, DOMElement $root)
	{
		return; // Done in _setPrice() - see comments there

		$priceSet = $this->_dataContainer->getMailplusSpecialprice();
		if (!$priceSet)
			return;

		$price = $priceSet['final'];

		$el = $dom->createElement('mailplus:aanbieding');
		$text = $dom->createTextNode($price);
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}

	protected function _setImage(DOMDocument $dom, DOMElement $root)
	{
		$imageSet = $this->_dataContainer->getMailplusImage();
		if ( !$imageSet || !is_array($imageSet) )
			return;


		if ( array_key_exists('base', $imageSet) && $imageSet['base'])
		{
			$el = $dom->createElement('mailplus:afbeeldinggroot');
			$text = $dom->createTextNode($imageSet['base']);
			$el->appendChild($text);
			$root->appendChild($el);
		}

		if ( array_key_exists('small', $imageSet) && $imageSet['small'])
		{
			$el = $dom->createElement('mailplus:afbeelding');
			$text = $dom->createTextNode($imageSet['small']);
			$el->appendChild($text);
			$root->appendChild($el);
		}

		if ( array_key_exists('thumbnail', $imageSet) && $imageSet['thumbnail'])
		{
			$el = $dom->createElement('mailplus:afbeeldingklein');
			$text = $dom->createTextNode($imageSet['thumbnail']);
			$el->appendChild($text);
			$root->appendChild($el);
		}

		$this->_called = true;
	}

	protected function _setSku(DOMDocument $dom, DOMElement $root)
	{
		$value = $this->_dataContainer->getMailplusSku();
		if (!$value)
			return;

		$el = $dom->createElement('mailplus:artikelnummer');
		$text = $dom->createTextNode($value);
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}

	protected function _setQty(DOMDocument $dom, DOMElement $root)
	{
		$value = $this->_dataContainer->getMailplusQty();
		if (!$value)
			return;

		$el = $dom->createElement('mailplus:voorraad');
		$text = $dom->createTextNode($value);
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}

	protected function _setBackorders(DOMDocument $dom, DOMElement $root)
	{
		$value = $this->_dataContainer->getMailplusBackorders();
		if (!$value)
			return;

		$el = $dom->createElement('mailplus:backorders');
		$text = $dom->createTextNode($value);
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}

	protected function _setWeight(DOMDocument $dom, DOMElement $root)
	{
		$value = $this->_dataContainer->getMailplusWeight();
		if (!$value)
			return;

		$el = $dom->createElement('mailplus:gewicht');
		$text = $dom->createTextNode($value);
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}

	protected function _setLogo(DOMDocument $dom, DOMElement $root)
	{
		$value = $this->_dataContainer->getMailplusLogo();
		if (!$value)
			return;

		$el = $dom->createElement('mailplus:logo');
		$text = $dom->createTextNode($value);
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}

	protected function _setIsInStock(DOMDocument $dom, DOMElement $root)
	{
		//print_r($this->_dataContainer->getExtension('Mailplus') );
		$value = $this->_dataContainer->getMailplusIsInStock();
		if (NULL === $value)
			return;

		$el = $dom->createElement('mailplus:op_voorraad');
		$text = $dom->createTextNode( true === $value? '1':'0' );
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}

	protected function _setShortDescription(DOMDocument $dom, DOMElement $root)
	{
		//print_r($this->_dataContainer->getExtension('Mailplus') );
		$value = $this->_dataContainer->getMailplusShortDescription();
		if (!$value)
			return;

		$el = $dom->createElement('mailplus:kortebeschrijving');
		$text = $dom->createTextNode($value);
		$el->appendChild($text);
		$root->appendChild($el);
		$this->_called = true;
	}
		
}
