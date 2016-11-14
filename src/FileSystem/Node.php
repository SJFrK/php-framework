<?php

namespace Tivoh\FileSystem;

class Node {
	protected $base;
	protected $path;
	protected $size;

	public function __construct($path, $base = null) {
		$this->setBaseDir($base);

		$this->path = $this->parsePath($path);
		$this->size = -1;
	}

	public function exists() {
		return file_exists($this->path);
	}

	public function parsePath($path) {
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$parts = explode('/', $path);
		$absolutes = array();

		foreach ($parts as $part) {
			if ('.'  == $part) {
				continue;
			}

			if ('..' == $part) {
				array_pop($absolutes);
			}

			else {
				$absolutes[] = $part;
			}
		}

		$path = implode('/', $absolutes);

		$path = preg_replace('#//+#', '/', $path);
		$path = preg_replace('#/$#', '', $path);

		return $path;
	}

	public function getChildren($sorter = null) {
		$path = $this->getPath();
		$handle = opendir($path);
		$nodes = [];

		if ($handle) {
			while (false !== ($node = readdir($handle))) {
				if ($node === '.' || $node === '..') {
					continue;
				}

				$nodes[] = new Node($path . '/' . $node, $this->getBaseDir());
			}

			closedir($handle);
		}

		if ($sorter !== false) {
			if (!is_callable($sorter)) {
				$sorter = function($a, $b) {
					return strcmp($a->getName(), $b->getName());
				};
			}

			uasort($nodes, $sorter);
		}

		return $nodes;
	}

	public function getBaseDir() {
		return $this->base;
	}

	public function setBaseDir($path) {
		$this->base = $this->parsePath($path);
	}

	public function getName() {
		return basename($this->getPath());
	}

	public function getRelativePath($base = null) {
		if ($base == null && $this->base != null) {
			$base = $this->getBaseDir();
		}

		$base = preg_replace('#/$#', '', $base);

		if (!preg_match('#^' . preg_quote($base, '#') . '#', $this->path)) {
			return false;
		}

		$path = preg_replace('#^' . preg_quote($base, '#') . '#', '', $this->path);
		$path = preg_replace('#//+#', '/', '/' . $path);
		$path = preg_replace('#^/#', '', $path);
		$path = preg_replace('#/$#', '', $path);

		return $path;
	}

	public function getParent() {
		return new Node(dirname($this->path), $this->base);
	}

	public function getPath() {
		return $this->path;
	}

	public function getSize($cached = true) {
		$path = $this->getPath();

		if ($cached && $this->size != -1) {
			return $this->size;
		}

		$this->size = -1;

		if (is_file($path)) {
			$this->size = filesize($path);
		}
		elseif (is_dir($path)) {
			$this->size = 0;
			$handle = opendir($path);

			if ($handle) {
				while (false !== ($node = readdir($handle))) {
					if ($node === '.' || $node === '..') {
						continue;
					}

					$node_path = $path . '/' . $node;
					$node_obj = new Node($node_path);
					$this->size += $node_obj->getSize();
				}
			}
		}

		return $this->size;
	}

	public function getType() {
		if (is_link($this->getPath())) {
			return 'link';
		}

		return filetype($this->path);
	}

	public function getMimeType() {
		$finfo = finfo_open(FILEINFO_MIME);
		return finfo_file($finfo, $this->getPath());
	}
}
