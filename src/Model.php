<?php

namespace Tivoh;

abstract class Model {
	protected static $table = null;
	protected static $fields = null;
	protected static $primaryKey = 'id';
	protected $data;

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
		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}
	}

	public function __set($key, $val) {
		$this->data[$key] = $val;
	}

	public function fromArray($data) {
		$this->data = $data;
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

		foreach (static::$fields as $field) {
			$placeholders[] = ':' . $field;
			$fields[] = static::escape($field);
			$data[':' . $field] = $this->__get($field);
		}

		$fields = implode(', ', $fields);
		$placeholders = implode(', ', $placeholders);

		$st = $db->prepare('INSERT INTO ' . static::escape(static::$table) . ' (' . $fields . ') VALUES (' . $placeholders . ')');

		return $st->execute($data);
	}

	public function update() {
		$db = Database::getHandle();

		$data = [];
		$fields = [];

		foreach (static::$fields as $field) {
			$fields[] = static::escape($field) . ' = :' . $field;
			$data[':' . $field] = $this->__get($field);
		}

		$fields = implode(', ', $fields);

		$st = $db->prepare('UPDATE ' . static::escape(static::$table) . ' SET ' . $fields . ' WHERE ' . static::escape(static::$primaryKey) . ' = :' . static::$primaryKey);

		return $st->execute($data);
	}

	public function delete() {
		$db = Database::getHandle();
		$st = $db->prepare('DELETE FROM `' . str_replace('`', '', static::$table) . '` WHERE ' . static::escape(static::$primaryKey) . ' = ?');

		return $st->execute([$this->__get(static::$primaryKey)]);
	}

	public static function escape($text) {
		return '`' . str_replace('`', '', $text) . '`';
	}

	public static function find($key, $val) {
		$db = Database::getHandle();

		if (static::$table == null) {
			throw new \LogicException(get_called_class() . '::$table is not set.');
		}

		if (static::$fields == null) {
			throw new \LogicException(get_called_class() . '::$fields is not set.');
		}

		if (static::$primaryKey == null) {
			throw new \LogicException(get_called_class() . '::$primaryKey is not set.');
		}

		$st = $db->prepare('SELECT * FROM ' . static::escape(static::$table) . ' WHERE ' . static::escape($key) . ' = ?');
		$st->execute([$val]);
		$data = $st->fetch(\PDO::FETCH_ASSOC);

		if ($data !== false) {
			return new static($data);
		}

		return null;
	}
}
