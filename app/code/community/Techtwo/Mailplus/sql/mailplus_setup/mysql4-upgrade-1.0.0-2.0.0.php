<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->run("DROP TABLE IF EXISTS `{$this->getTable('mailplus/syncqueue')}`");

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

$db = $this->getConnection();
$db->dropForeignKey($this->getTable('mailplus/product'), 'mailplus_product_ibfk_1');
$db->dropForeignKey($this->getTable('mailplus/product'), 'mailplus_product_ibfk_2');
$db->dropForeignKey($this->getTable('mailplus/product'), 'mailplus_product_ibfk_3');

// This foreign key was added without a name. So make sure to delete it with the correct name 
$db->dropForeignKey($this->getTable('mailplus/abandoned_campaign'), $this->getTable('mailplus/abandoned_campaign') . '_ibfk_1');
$db->dropForeignKey($this->getTable('mailplus/abandoned_campaign'), $this->getTable('mailplus/abandoned_campaign') . '_ibfk_2');

$this->endSetup();