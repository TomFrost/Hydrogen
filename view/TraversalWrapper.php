<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\exceptions\NoSuchVariableException;

class TraversalWrapper {
	protected $var;
	protected $nullIfNotFound;
	protected $traversed;

	public function __construct(&$var, &$nullIfNotFound=false,
			&$traversed=array()) {
		$this->var = &$var;
		$this->nullIfNotFound = &$nullIfNotFound;
		$this->traversed = &$traversed;
	}

	public function getValue() {
		return $this->var;
	}

	public function __get($name) {
		$this->traversed[] = $name;
		if ($this->nullIfNotFound && is_null($this->var))
			return new TraversalWrapper($this->var, true, $this->traversed);
		if (is_array($this->var) && isset($this->var[$name])) {
			return new TraversalWrapper($this->var[$name],
				$this->nullIfNotFound, $this->traversed);
		}
		if (isset($this->var->$name)) {
			return new TraversalWrapper($this->var->$name,
				$this->nullIfNotFound, $this->traversed);
		}
		if (is_object($this->var)) {
			$methods = get_class_methods($this->var);
			if (in_array(($func = "get" . ucfirst($name)), $methods) ||
					in_array(($func = "is" . ucfirst($name)), $methods) ||
					in_array(($func = "get_" . $name), $methods) ||
					in_array(($func = "is_" . $name), $methods))
				return new TraversalWrapper(
					call_user_func(array($this->var, $func)),
					$this->nullIfNotFound, $this->traversed);
		}
		if ($this->nullIfNotFound) {
			$var = null;
			return new TraversalWrapper($var, true, $this->traversed);
		}
		$varName = implode('.', $this->traversed);
		$e = new NoSuchVariableException(
			"Variable does not exist in context: $varName");
		$e->variable = $varName;
		throw $e;
	}

	public function __set($name, $val) {
		if (is_array($this->var))
			$this->var[$name] = $val;
		else if (is_object($this->var))
			$this->var->$name = $val;
		else
			throw new NoSuchVariableException("Cannot set member variable '$name' on a non-object.");
	}

	public function __isset($name) {
		if (is_array($this->var) && isset($this->var[$name]))
			return true;
		if (isset($this->var->$name))
			return true;
		if (is_object($this->var)) {
			$methods = get_class_methods($this->var);
			if (in_array(($func = "get" . ucfirst($name)), $methods) ||
					in_array(($func = "is" . ucfirst($name)), $methods) ||
					in_array(($func = "get_" . $name), $methods) ||
					in_array(($func = "is_" . $name), $methods))
				return true;
		}
		return false;
	}

	public function __unset($name) {
		if (is_array($this->var) && isset($this->var[$name]))
			unset($this->var[$name]);
		else if (isset($this->var->$name))
			unset($this->var->$name);
	}

	public function __toString() {
		return $this->var;
	}
}

?>