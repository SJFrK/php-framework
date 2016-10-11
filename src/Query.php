<?php

namespace Tivoh;

class Query {
	protected $whereStr = '';
	protected $paramCount = 0;
	protected $params = array();
	protected $table;
	protected $primaryKey;
	protected $db;

	public function __construct($db, $table, $primaryKey) {
		$this->table = $table;
		$this->primaryKey = $primaryKey;
		$this->db = $db;
	}

	public function where($keys, $values, $operators = '=', $relation = 'AND') {
		if (!is_array($keys)) {
			$keys = array($keys);
			$values = array($values);
		}

		if (!is_array($operators)) {
			$operators = array();
			for ($i = 0, $n = count($keys); $i < $n; ++$i) {
				$operators[] = '=';
			}
		}

		if (!(count($keys) === count($values) && count($keys) === count($operators))) {
			throw new \Exception("All arrays must have the same amount of items");
		}

		$relation = strtoupper(trim($relation));

		if (!strstr('AND;OR', $relation)) {
			throw new \Exception("Wrong relation");
		}

		$str = '';

		for ($i = 0, $n = count($keys); $i < $n; ++$i) {
			if ($i > 0) {
				$str .= ' ' . $relation . ' ';
			}

			$str .= '`' . $this->sanitize($keys[$i]) . '` ' . $this->sanitize($operators[$i]) . ' :P' . $this->paramCount;

			$this->params[':P' . $this->paramCount++] = $values[$i];
		}

		$this->whereStr .= '(' . $str . ')';

		return $this;
	}

	public function andWhere($keys, $values, $operators = '=', $relation = 'AND') {
		$this->whereStr .= ' AND ';
		return $this->where($keys, $values, $operators, $relation);
	}

	public function orWhere($keys, $values, $operators = '=', $relation = 'AND') {
		$this->whereStr .= ' OR ';
		return $this->where($keys, $values, $operators, $relation);
	}

	public function getAll() {
		$st = $this->run();

		if ($st !== false) {
			return $st->fetchAll(\PDO::FETCH_OBJ);
		}

		return array();
	}

	public function getOne() {
		$st = $this->run();

		if ($st !== false) {
			return $st->fetch(\PDO::FETCH_OBJ);
		}

		return false;
	}

	public function run() {
		$st = $this->db->prepare('SELECT ' . $this->primaryKey . ' FROM ' . $this->table  . ' WHERE ' . $this->whereStr);

		if ($st->execute($this->params)) {
			return $st;
		}

		return false;

	}

	protected function sanitize($str) {
		$str = str_replace(array('`', ';'), '', $str);
		return $str;
	}
}
