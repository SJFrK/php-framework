<?php

namespace trollsoft;

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

	public function contains($key) {
		return array_key_exists($key, $this->data);
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
