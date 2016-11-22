<?php

namespace Tivoh;

abstract class Model {
	protected static $table = null;
	protected static $fields = null;
	protected static $relations = [];
	protected static $primaryKey = 'id';
	protected $data;
	protected $dirty = false;

	public final function __construct(array $data = null) {
		if (static::$table == null) {
			throw new \LogicException(get_called_class() . '::$table is not set.');
		}

		if (static::$fields == null) {
			throw new \LogicException(get_called_class() . '::$fields is not set.');
		}

		if (static::$primaryKey == null) {
			throw new \LogicException(get_called_class() . '::$primaryKey is not set.');
		}

		if ($data != null) {
			$this->fromArray($data);
		}
	}

	public function __get($key) {
		$key = static::normalizeKey($key);

		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}

		if (array_key_exists($key, static::$relations)) {
			$relation = static::$relations[$key];
			$class = $relation[0];
			$foreignKey = $relation[1];
			$object = $class::find($class::$primaryKey, $this->__get($foreignKey));
			$this->__set($key, $object);

			return $object;
		}

		return null;
	}

	public function __set($key, $val) {
		$key = static::normalizeKey($key);

		$this->data[$key] = $val;
		$this->dirty = true;
	}

	public function fromArray($data) {
		foreach ($data as $key => $val) {
			$this->__set($key, $val);
		}
	}

	public function save() {
		$self = static::find(static::$primaryKey, $this->__get(static::$primaryKey));

		if ($self !== null) {
			return $this->update();
		}
		else {
			return $this->insert();
		}
	}

	public function insert() {
		$db = Database::getHandle();

		$data = [];
		$placeholders = [];
		$fields = [];

		foreach (static::$fields as $field => $type) {
			$placeholders[] = ':' . $field;
			$fields[] = Database::sanitize($field);
			$data[':' . $field] = $this->__get($field);
		}

		$fields = implode(', ', $fields);
		$placeholders = implode(', ', $placeholders);

		$st = $db->prepare('INSERT INTO ' . Database::sanitize(static::$table) . ' (' . $fields . ') VALUES (' . $placeholders . ')');

		return $st->execute($data);
	}

	public function update() {
		foreach (static::$relations as $field => $relation) {
			if (array_key_exists($field, $this->data)) {
				$this->__get($field)->save();
			}
		}

		if ($this->dirty == false) {
			return true;
		}

		$db = Database::getHandle();

		$data = [];
		$fields = [];

		foreach (static::$fields as $field => $type) {
			$fields[] = Database::sanitize($field) . ' = :' . $field;
			$data[':' . $field] = $this->__get($field);
		}

		$fields = implode(', ', $fields);

		$st = $db->prepare('UPDATE ' . Database::sanitize(static::$table) . ' SET ' . $fields . ' WHERE ' . Database::sanitize(static::$primaryKey) . ' = :' . static::$primaryKey);

		return $st->execute($data);
	}

	public function delete() {
		$db = Database::getHandle();
		$st = $db->prepare('DELETE FROM `' . str_replace('`', '', static::$table) . '` WHERE ' . Database::sanitize(static::$primaryKey) . ' = ?');

		return $st->execute([$this->__get(static::$primaryKey)]);
	}

	public static function normalizeKey($key) {
		return strtolower(preg_replace('/([A-Z])/', '_$1', $key));
	}

	public static function find($key, $val, $limit = 1) {
		return static::findArray([$key => $val], $limit);
	}

	public static function findArray($arr, $limit = 1) {
		if (static::$table == null) {
			throw new \LogicException(get_called_class() . '::$table is not set.');
		}

		if (static::$fields == null) {
			throw new \LogicException(get_called_class() . '::$fields is not set.');
		}

		if (static::$primaryKey == null) {
			throw new \LogicException(get_called_class() . '::$primaryKey is not set.');
		}

		$query = Query::get(static::$table);

		foreach ($arr as $key => $val) {
			$key = static::normalizeKey($key);
			$query->where($key, $val);
		}

		if (is_numeric($limit) && $limit >= 1) {
			$rows = $query->some($limit, -1, \PDO::FETCH_ASSOC);
		}
		else {
			$rows = $query->all(-1, \PDO::FETCH_ASSOC);
		}

		if (is_numeric($limit) && $limit == 1) {
			if (count($rows) > 0) {
				return new static($rows[0]);
			}
		}
		else {
			if (count($rows) > 0) {
				$out = [];
			
				foreach ($rows as $row) {
					$out[]= new static($row);
				}

				return $out;
			}
		}

		return null;
	}
}
