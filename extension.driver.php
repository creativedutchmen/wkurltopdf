<?php

use wkurltopdf\queue;

require_once(EXTENSIONS . '/wkurltopdf/lib/queue.php');
require_once(EXTENSIONS . '/wkurltopdf/lib/queued_element.php');

class Extension_Wkurltopdf extends Extension
{
	public function install()
	{
		Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_pdf_url` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`anchor_label` VARCHAR(255) DEFAULT NULL,
					`url_field` INT(11) UNSIGNED NOT NULL,
					`new_window` ENUM('yes', 'no') DEFAULT 'no',
					`hide` ENUM('yes', 'no') DEFAULT 'no',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		return Symphony::Database()->query(
			sprintf(
				"CREATE TABLE IF NOT EXISTS `%s` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`entry_id` INT(11) UNSIGNED NOT NULL,
					`field_id` INT(11) UNSIGNED NULL,
					`status` VARCHAR(255) DEFAULT NULL,
					`modified` datetime NOT NULL,
					PRIMARY KEY (`id`)
				)",
				Queue::QUEUE_TABLE_NAME
			)
		);
	}

	public function uninstall()
	{
		Symphony::Database()->query("DROP TABLE IF EXISTS `tbl_fields_pdf_url`");
		return Symphony::Database()->query(
			sprintf(
				"DROP TABLE IF EXISTS `%s`",
				Queue::QUEUE_TABLE_NAME
			)
		);
	}

}