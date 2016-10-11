<?php

namespace Tivoh;

class Stack {
	protected $data;
	protected $limit;
	
	public function __construct($limit = 10) {
		$this->data = array();
		$this->limit = $limit;
	}
	
	public function push($item) {
		if (count($this->data) < $this->limit) {
			$this->data[] = $item;
		}
		else {
			throw new RunTimeException('StackOverflow'); 
		}
	}
	
	public function pop() {
		if ($this->empty()) {
			throw new RunTimeException('StackUnderflow');
		}
		else {
			return array_pop($this->data);
		}
	}
	
	public function top() {
		return $this->data[count($this->data) - 1];
	}
	
	public function empty() {
		return empty($this->data);
	}
}
