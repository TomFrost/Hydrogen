<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\TraversalWrapper;
use hydrogen\view\exceptions\NoSuchVariableException;

class ContextStack {
	protected $stack;
	protected $stackLevel;

	public function __construct($initialData=false) {
		$this->stack = array(array());
		$this->stackLevel = array();
		if (is_array($initialData))
			$this->setArray($initialData);
	}
	
	public function __get($key) {
		return $this->getWrapped($key);
	}
	
	public function __set($key, $value) {
		$this->set($key, $value);
	}
	
	public function __isset($key) {
		return $this->keyExists($Key);
	}
	
	public function __unset($key) {
		$this->delete($key);
	}

	public function push() {
		array_push($this->stack, array());
	}

	public function pop() {
		return new ContextStack(array_pop($this->stack));
	}

	public function set($key, $value, $forceTop=false) {
		$level = false;
		if (!$forceTop) {
			for ($i = count($this->stack) - 1; $i >= 0 ; $i--) {
				if (array_key_exists($key, $this->stack[$i])) {
					$level = $i;
					break;
				}
			}
		}
		if ($level === false) {
			$level = count($this->stack) - 1;
			$this->stackLevel[$key] = $level;
		}
		$this->stack[$level][$key] = $value;
	}

	public function setArray($kvArray) {
		foreach ($kvArray as $key => $value)
			$this->set($key, $value);
	}

	public function get($key, $nullIfNotFound=false) {
		if (!$this->keyExists($key)) {
			if ($nullIfNotFound)
				return null;
			else {
				$e = new NoSuchVariableException(
					"Variable does not exist in context: $key");
				$e->variable = $key;
				throw $e;
			}
		}
		return $this->stack[$this->stackLevel[$key]][$key];
	}

	public function getWrapped($key, $nullIfNotFound=false) {
		$var = $this->get($key, $nullIfNotFound);
		$traversed = array($key);
		return new TraversalWrapper($var, $nullIfNotFound, $traversed);
	}

	public function delete($key) {
		if (!$this->keyExists($key)) {
			$e = new NoSuchVariableException(
				"Variable does not exist in context: $key");
			$e->variable = $key;
			throw $e;
		}
		unset($this->stack[$this->stackLevel[$key]][$key]);
	}

	public function keyExists($key) {
		return isset($this->stackLevel[$key]);
	}
}

?>