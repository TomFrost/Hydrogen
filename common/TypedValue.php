<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\common;

/**
 * TypedValue is simply a resource-friendly method of storing a value and
 * some sort of identifier that describes it, rather than using a much
 * more expensive array for such a task.
 */
class TypedValue {

	/**
	 * The type of variable stored in $value.  This is arbitrary and can
	 * be used however it's required.
	 */
	public $type;

	/**
	 * The value of type $type.  This is arbitrary and can be used however
	 * it's required.
	 */
	public $value;

	/**
	 * Creates a new TypedValue.  The type and value passed in the arguments
	 * will become available in the $type and $value member variables.
	 *
	 * @param mixed $type A 'type' to associate with the value.  The type can
	 *      be any kind of native type or object.
	 * @param mixed $value The value to be stored.
	 */
	public function __construct($type, $value) {
		$this->type = &$type;
		$this->value = &$value;
	}

	/**
	 * When treated as a string, a TypedValue will default to its value's
	 * string representation.
	 *
	 * @return string A string representation of the stored value.
	 */
	public function __toString() {
		return $this->value;
	}
	
	/**
	 * Allows a TypedValue to be used with the 'clone' keyword.
	 *
	 * @return TypedValue A new copy of this TypedValue object.
	 */
	public function __clone() {
		return new TypedValue($this->type, $this->value);
	}
}

?>