<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->run("DROP TABLE IF EXISTS `{$this->getTable('mailplus/restqueue')}`");

$this->run("
		CREATE TABLE `{$this->getTable('mailplus/restqueue')}` (
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

$this->endSetup();