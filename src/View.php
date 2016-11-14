<?php

namespace Tivoh;

class View {
	protected $file;
	protected $html;
	protected $context;
	protected $blocks;
	protected $blockStack;
	protected $extend = null;

	protected static $defaultContext;

	public static function setDefaultContext($context) {
		static::$defaultContext = $context;
	}

	public function __construct($file, Context $context = null, $blocks = []) {
		$this->file = $file;
		$this->blocks = $blocks;
		$this->blockStack = new Stack();

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
		$buffer = ob_get_clean();

		if ($this->extend != null) {
			$view = new View($this->extend, $this->getContext(), $this->blocks);
			return $view->parse();
		}

		return $buffer;
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

	private function formatTime($time, $format = '%A, %B %e, %Y, %H:%M %Z') {
		return strftime($format, strtotime($time));
	}

	private function block($name) {
		$this->blockStack->push($name);
		ob_start();
	}

	private function endblock() {
		$name = $this->blockStack->pop();

		$buffer = ob_get_clean();

		$this->processBlock($name, $buffer);
	}

	private function emptyblock($name) {
		$this->processBlock($name, '');
	}

	private function processBlock($name, $buffer) {
		if (array_key_exists($name, $this->blocks)) {
			echo $this->blocks[$name];
		}
		elseif ($this->extend == null) {
			echo $buffer;
		}
		else {
			$this->blocks[$name] = $buffer;
		}
	}

	private function extend($view) {
		$path = dirname($this->file) . '/';
		$this->extend = $path . $view . '.php';
	}
}
