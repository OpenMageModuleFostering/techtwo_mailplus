<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->run("
		CREATE TABLE `{$this->getTable('mailplus/info')}` (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '' ,
		`value` varchar(255) NOT NULL DEFAULT '' ,
		PRIMARY KEY ( `id` ),
		KEY `name` (`name`)
) ENGINE = InnoDB;
");

$this->endSetup();