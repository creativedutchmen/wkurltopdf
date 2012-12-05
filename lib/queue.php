<?php

namespace wkurltopdf;

class Queue
{

	protected $database;
	protected $element;

	const QUEUE_TABLE_NAME 	= 'tbl_wkhtmltopdf_queue';
	const STATUS_QUEUED 	= 'queued';
	const STATUS_PROCESSING	= 'processing';
	const STATUS_COMPLETE	= 'completed';
	const STATUS_FAILED		= 'failed';
	const RETRY_AFTER		= 300;

	public function __construct(MySQL $database = null, QueuedElement $element = null)
	{
		if (is_null($database)) {
			try {
				$this->database = Symphony::Database();
			}
			catch(Exception $e) {
				throw new QueueException('No Database class given, and Symphony database can not be reached.');
			}
		}
		else {
			$this->database = $database;
		}
		if (is_null($element)) {
			$this->element = new QueuedElement();
		}
		else {
			$this->element 	= $element;
		}
	}

	public function count($ignore_processing = true)
	{
		if (true === $ignore_processing) {
			$get_count = 'SElECT count(entry_id) as count from `' . self::QUEUE_TABLE_NAME . '` WHERE \`status\` = \''.self::STATUS_QUEUED.'\'';
		}
		else{
			$get_count = 'SElECT count(entry_id) as count from `' . self::QUEUE_TABLE_NAME . '`';
		}
		return $this->database->fetchVar('count', $get_count);
	}

	public function dequeue()
	{
		if ($this->count() > 0) {
			$get_oldest_entry = 'SELECT id, entry_id, field_id from `' . self::QUEUE_TABLE_NAME.'` WHERE \`status\` = \''.self::STATUS_QUEUED.'\' ORDER BY `id` ASC LIMIT 1';
			$this->database->query($get_oldest_entry);
			$properties = $this->database->fetchRow();

			$element = clone $this->element;

			$element->set('id',$properties['id']);
			$element->set('entry_id', $properties['entry_id']);
			$element->set('field_id', $properties['field_id']);
			$element->set('status',self::STATUS_PROCESSING);

			$this->update($element);

			return $element;
		}
		else {
			throw new QueueException('Can not dequeue an empty queue');
		}
				
	}

	public function enqueue(QueuedElement $element)
	{
		$result = $this->database->insert(
			array(
				'entry_id'	=> $element->get('entry_id'),
				'field_id'	=> $element->get('field_id'),
				'status'	=> self::STATUS_QUEUED
			),
			self::QUEUE_TABLE_NAME
		);
		if (false === $result) {
			throw new QueueException('entry is already queued');
		}
	}

	public function update(QueuedElement $element)
	{
		if ($element->get('status') == self::STATUS_FAILED || $element->get('status') == self::STATUS_COMPLETE) {
			$this->database->delete(self::QUEUE_TABLE_NAME, sprintf('`id` = %i', $element->get('id')));
		}
		else {
			$properties = array_merge($element->get(), array('modified'	=> date('Y-m-d H:i:s')));
			$this->database->update($properties, self::QUEUE_TABLE_NAME);
		}
	}

	public function cleanUp()
	{
		$this->database->update(
			array(
				'status'	=> self::STATUS_QUEUED,
				'modified'	=> date('Y-m-d H:i:s')
			),
			self::QUEUE_TABLE_NAME,
			sprintf('
				`status` = \'%s\' AND `timestamp` < NOW() - INTERVAL %i SECOND',
				self::STATUS_PROCESSING,
				self::RETRY_AFTER
			)
		);
	}	
}

class QueueException extends \Exception
{

}