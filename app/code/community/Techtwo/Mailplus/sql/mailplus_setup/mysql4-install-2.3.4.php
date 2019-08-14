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
// Since Magento 1.4+ is supported, plain SQL queries instead of DDL are used

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('mailplus/bounce')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mailplus_id` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `insertion` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `total_received` int(10) unsigned NOT NULL,
  `is_test` tinyint(1) NOT NULL,
  `is_customer_alerted` int(11) NOT NULL DEFAULT '0',
  `last_bounce_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailplus_id` (`mailplus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
");

$this->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('mailplus/product')}` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `catalog_product_entity_id` int(10) unsigned NOT NULL,
  `store_id` smallint(5) unsigned NOT NULL,
  `price` decimal(12,4) NOT NULL COMMENT 'The synchronized price',
  `checksum` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`entity_id`),
  UNIQUE KEY `catalog_product_entity_id_2` (`catalog_product_entity_id`,`store_id`),
  KEY `store_id` (`store_id`),
  KEY `catalog_product_entity_id` (`catalog_product_entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$this->run("
		CREATE TABLE IF NOT EXISTS `{$this->getTable('mailplus/restqueue')}` (
		`restqueue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`method` varchar(255) NOT NULL,
		`url` varchar(255) NOT NULL,
		`payload` text NULL,
		`tries` int(10) unsigned NOT NULL DEFAULT '0',
		`last_error` text NULL,
		`last_response` text NULL,
		`created_at` datetime NOT NULL,
		`last_run_at` datetime NOT NULL,
		`next_run_at` datetime NOT NULL,
		`site` int(10) unsigned NOT NULL,
		PRIMARY KEY (`restqueue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

$this->run("
		CREATE TABLE `{$this->getTable('mailplus/syncqueue')}` (
		`syncqueue_id` int(11) NOT NULL AUTO_INCREMENT,
		`synctype` varchar(10) NOT NULL,
		`websiteid` int(11) NOT NULL,
		`syncid` int(11) NOT NULL,
		`created_at` datetime NOT NULL,
		PRIMARY KEY (`syncqueue_id`),
		KEY `synctype` (`synctype`,`created_at`),
		KEY `websiteid` (`websiteid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
");


$this->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('mailplus/user')}` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) unsigned DEFAULT NULL,
  `mailplus_id` char(50) DEFAULT NULL,
  `store_id` smallint(5) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `is_test` tinyint(1) NOT NULL DEFAULT '0',
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `createts` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `mailplus_id` (`mailplus_id`),
  KEY `store_id` (`store_id`),
  KEY `store_customer` (`customer_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
");

$this->run("
	CREATE TABLE `{$this->getTable('mailplus/abandoned_campaign')}` (
		`quote_id` INT( 10 ) UNSIGNED NOT NULL ,
		`created_at` DATETIME NOT NULL ,
		PRIMARY KEY ( `quote_id` )
	) ENGINE = InnoDB;
");

$this->run("
		CREATE TABLE `{$this->getTable('mailplus/info')}` (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '' ,
		`value` varchar(255) NOT NULL DEFAULT '' ,
		PRIMARY KEY ( `id` ),
		KEY `name` (`name`)
) ENGINE = InnoDB;
");

$this->run("
ALTER TABLE `{$this->getTable('mailplus/user')}`
  ADD CONSTRAINT `mailplus_user_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `{$this->getTable('customer/entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mailplus_user_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;
");


$this->endSetup();
