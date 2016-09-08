<?php

/**
trollsoft PHP Framework

@author SJFrK <contact@q5n.de>
@version 1.0.0
*/

namespace trollsoft;

/**
	View

	Class for PHP inline templating.

	@author SJFrK <contact@q5n.de>
	@version 1.0.0
*/
class View {
	protected $file;
	protected $html;
	protected $context;

	protected static $defaultContext;

	public static function setDefaultContext($context) {
		static::$defaultContext = $context;
	}

	public function __construct($file, Context $context = null) {
		$this->file = $file;

		if ($context == null) {
			$this->context = new Context;
		}
		else {
			$this->context = $context;
		}
	}

	public function set($key, $val) {
		$this->context->set($key, $val);
	}

	public function get($key) {
		if ($this->context != null && $this->context->contains($key)) {
			return $this->context->get($key);
		}

		if (static::$defaultContext != null && static::$defaultContext->contains($key)) {
			return static::$defaultContext->get($key);
		}

		return null;
	}

	public function __set($key, $val) {
		$this->set($key, $val);
	}

	public function __get($key) {
		return $this->get($key);
	}

	public function setContext($context) {
		$this->context = $context;
	}

	public function getContext() {
		return $this->context;
	}

	public function parse() {
		ob_start();
		include $this->file;
		return ob_get_clean();
	}

	public function render() {
		echo $this->parse();
	}

	public function __call($name, $args) {
		if (is_callable($this->get($name))) {
			return call_user_func_array($this->get($name), $args);
		}
		return false;
	}

	public function call($name, ...$params) {
		if (is_callable($this->get($name))) {
			return call_user_func_array($this->get($name), $args);
		}
		return false;
	}

	private function import($param) {
		$path = dirname($this->file) . '/';
		$tpl = new View($path . $param . '.php', $this->getContext());
		echo $tpl->render();
	}

	private function formatTime($time) {
		return strftime('%A, %B %e, %Y, %H:%M %Z', strtotime($time));
	}
}
