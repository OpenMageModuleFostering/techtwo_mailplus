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
class Techtwo_Mailplus_Model_System_Config_Source_Campaign
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {

	    $aux = array();

	    $aux []= array(
		    'value' => '',
		    'label' => Mage::helper('adminhtml')->__('Disabled')
	    );

	    $triggers = $this->getTriggers();
	    if ( FALSE !== $triggers )
	    {

		    if ( 0 === count($triggers) )
		    {
			    $aux = array(array(
				    'value' => '',
				    'label' => Mage::helper('adminhtml')->__('No campaigns registered or cache')
			    ));
		    }

			foreach ( $triggers as $t )
			{
				/* @var $t Techtwo_Mailplus_Model_System_Campaign */

				if (!$t->hasTriggers())
					continue;

				$aux []= array(
					'value' => $t->getEncryptedId(),
					'label' => "{$t->getName()}" //  [{$t->getFirstTriggerName()}]
				);
			}
	    }
	    else
	    {
		    // this is the user current value, so if the cache cannot be used or retrieved
		    // you actually won't lose your settings upon save (and no change)
			$aux []= array(
				'value' => '', //Mage::getStoreConfig('mailplus/campaign/newsletter'), // keeps the current value
				'label' => Mage::helper('mailplus')->__('Cache failed or authorize first')
			);
	    }


	    return $aux;
    }

	/**
	 * @return array|bool Return a array of Techtwo_Mailplus_Model_System_Campaign as triggers or FALSE on error
	 */
	public function getTriggers()
	{
		try {
			$store = Mage::app()->getFrontController()->getRequest()->getParam('store');
			$website = Mage::app()->getFrontController()->getRequest()->getParam('website');

			/* @var $helper Techtwo_Mailplus_Helper_Rest */
			$helper = Mage::helper('mailplus/rest');
			$client = null;
			if ($website)
				$client = $helper->getClientByWebsite( $website );
			elseif ($store)
				$client = $helper->getClientByStore( $store );

			if ( $client )
				return $helper->getTriggers($client);
			}
		catch (Exception $e) {
			Mage::logException($e);
		}	

		return FALSE;
	}

}
