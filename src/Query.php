<?php

namespace Tivoh;

class Query {
	protected $whereStr = '';
	protected $paramCount = 0;
	protected $params = array();
	protected $table;
	protected $primaryKey;
	protected $query;
	protected $limit;
	protected $offset;

	protected function __construct($query) {
		$this->query = $query;
	}

	public static function get($table) {
		return new static('SELECT * FROM ' . Database::sanitize($table));
	}

	public function where($key, $value) {
		if ($this->whereStr != '') {
			$this->whereStr .= ' WHERE ';
		}
		else {
			$this->whereStr .= ' AND ';
		}

		$this->whereStr .= Database::sanitize($key) . ' = :p' . $this->paramCount;
		$this->params[':p' . $this->paramCount] = $value;
		
		++$this->paramCount;

		return $this;
	}

	public function all($offset = -1) {
		$this->limit = -1;
		$this->offset = $offset;

		$st = $this->run();

		if ($st !== false) {
			return $st->fetchAll(\PDO::FETCH_OBJ);
		}

		return [];
	}

	public function some($limit, $offset) {
		$this->limit = $limit;
		$this->offset = $offset;
	}

	public function one($offset = -1) {
		$this->limit = 1;
		$this->offset = $offset;

		$st = $this->run();

		if ($st !== false) {
			return $st->fetch(\PDO::FETCH_OBJ);
		}

		return null;
	}

	public function run() {
		if (is_numeric($this->limit) && $this->limit > 0) {
			$this->query .= ' LIMIT ' . $this->limit;
		}

		if (is_numeric($this->offset) && $this->offset > 0) {
			$this->query .= ' OFFSET ' . $this->offset;
		}

		$db = Database::getHandle();

		$st = $db->prepare($this->query);

		if ($st->execute($this->params)) {
			return $st;
		}

		return false;
	}
}
