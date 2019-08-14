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
class Techtwo_Mailplus_Helper_Cron extends Mage_Core_Helper_Abstract
{
	const OFTEN_START = 'cron_often_start';
	const OFTEN_END = 'cron_often_end';
	const HOURLY_START = 'cron_hourly_start';
	const HOURLY_END = 'cron_hourly_end';
	
	protected $_logHdl;
	protected $_log_prefix;

	public function setLogHandle($logHdl)
	{
		$this->_logHdl = $logHdl;
	}

	public function setLogPrefix($prefix)
	{
		$this->_log_prefix = $prefix;
	}

	public function eventLog( $str )
	{
		if ($this->_logHdl)
			return fwrite( $this->_logHdl, $this->_log_prefix.$str.PHP_EOL );
		return false;
	}
}