<?php

namespace wkurltopdf;

class QueuedElement
{

	protected $properties = array();

	public function set($key, $value)
	{
		$this->properties[$key] = $value;
	}

	public function get($key = null)
	{
		if (!is_null($key)) {
			return $this->properties;
		}
		else {
			return isset($this->properties[$key])?$this->properties[$key]:null;
		}
		
	}
}