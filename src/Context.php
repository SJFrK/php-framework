<?php

/**
	trollsoft PHP Framework

	@author SJFrK <contact@q5n.de>
	@version 1.0.0
*/

namespace trollsoft;

/**
	Context

	@author SJFrK <contact@q5n.de>
	@version 1.0.0
*/
class Context {
	protected $data = array();

	public function set($key, $val) {
		$this->data[$key] = $val;
	}

	public function get($key) {
		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}
		return null;
	}

	public function __set($key, $val) {
		$this->set($key, $val);
	}

	public function __get($key) {
		return $this->get($key);
	}

	public function fromArray(array $data) {
		foreach ($data as $key => $val) {
			$this->data[$key] = $val;
		}
	}

	public function toArray() {
		return $this->data;
	}
}
