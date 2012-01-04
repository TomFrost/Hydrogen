<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\exceptions\NoSuchVariableException;

/**
 * TraversalWrapper is a helper class for the Hydrogen
 * {@link \hydrogen\view\ContextStack}.  It allows all members, pseudo-members,
 * arrays keys, and getter functions of any wrapped object to be accessed as
 * though it's a member variable.  A variable that could normally be called
 * like this in PHP:
 * 
 * <pre>
 * $users['ted']->getGroup()->name
 * </pre>
 * 
 * ...can be called like this if $users has been wrapped in a TraversalWrapper:
 * 
 * <pre>
 * $users->ted->group->name
 * </pre>
 * 
 * The biggest benefit of this, other than the shorter syntax, is that the
 * template programmer does not have to be aware which properties are
 * functions, arrays, objects, member variables, etc.  Properties can be
 * "dug" through while remaining blissfully unaware of object types.
 *
 * The other benefit of this format is that object types and functions can be
 * completely changed at the controller level without the template (or the
 * template developer) ever needing to be made aware.
 *
 * While TraversalWrapper can be instantiated by the public, a
 * TraversalWrapper-wrapped variable is most commonly retrieved from an
 * instance of {@link \hydrogen\view\ContextStack} either by calling
 * {@link \hydrogen\view\ContextStack::getWrapped()} or by using ContextStack's
 * own __get magic method.  A common example ($context, in this example, is
 * an instance of ContextStack):
 *
 * <pre>
 * $context->user->group->permissions->can_ban_users->getValue()
 * </pre>
 *
 * TraversalWrapper also supports the __isset and __unset magic methods as
 * would be expected, but also supports __set in a way that allows variables
 * to be traversed up through the one that needs to be set, and accepts a
 * value for it.  For example, instead of doing this:
 *
 * <pre>
 * $this->users['ted']->profile['AIM_name'] = "Mosbius";
 * </pre>
 *
 * TraversalWrapper allows the programmer to do this:
 *
 * <pre>
 * $context->users->ted->profile->AIM_name = "Mosbius";
 * </pre>
 *
 * This way, the template programmer still does not need to know object types
 * or PHP specifics, and the backend programmer is free to change "profile"
 * from an associative array to, for example, a class with an AIM_name field,
 * without templates having to be updated to support this change.
 *
 * See the documentation for {@link __get()} for information on the order in
 * which TraversalWrapper attempts to access member variables and functions.
 */
class TraversalWrapper {
	protected $var;
	protected $nullIfNotFound;
	protected $traversed;

	/**
	 * Creates a new TraversalWrapper, wrapping an existing variable.
	 *
	 * @param mixed $var The existing variable to wrap in the new
	 * 		TraversalWrapper.
	 * @param boolean $nullIfNotFound true if the TraversalWrapper should
	 * 		evaluate to a NULL value when the next variable is not found
	 * 		(but still remain traversable to avoid errors) or false if a
	 * 		NoSuchVariableException should be thrown in this case.  If not
	 * 		specified, the default is false.
	 * @param array $traversed An array of variables that have been traversed
	 * 		in a chain up to this point.  For accurate error messages, an array
	 * 		containing only the name of the contained variable should be
	 * 		passed to the constructor when the TraversalWrapper is
	 * 		instantiated.
	 */
	public function __construct($var, &$nullIfNotFound=false,
			&$traversed=array()) {
		$this->var = &$var;
		$this->nullIfNotFound = &$nullIfNotFound;
		$this->traversed = &$traversed;
	}

	/**
	 * Gets the raw value from this TraversalWrapper.  If the TraversalWrapper
	 * is being used as a string, calling this function is not required as the
	 * {@link __toString()} magic method will kick in.  Otherwise, it is
	 * good practice to call this function once the end of the traversal chain
	 * has been reached.
	 *
	 * @return mixed The contents of the variable being wrapped by this
	 * 		TraversalWrapper.
	 */
	public function getValue() {
		return $this->var;
	}

	/**
	 * Gets the next variable in a TraversalWrapper chain.  This function is a
	 * PHP magic method and should never be called directly.
	 *
	 * TraversalWrapper will look for the variable in the following places,
	 * in this order:
	 *
	 * - Member variable ($currentVar->VARNAME) only if isset() returns true.
	 * - Array index ($currentVar[VARNAME]) only if isset() returns true and
	 * 		$currentVar is a PHP array.
	 * - The following methods if they exist (if $currentVar is an object):
	 * 		- $currentVar->getVARNAME()
	 * 		- $currentVar->isVARNAME()
	 * 		- $currentVar->get_VARNAME()
	 * 		- $currentVar->is_VARNAME()
	 * - Pseudo-member variable.  These are variables that can be accessed
	 * 		through a class's __get() magic method, but __isset() is not
	 * 		defined.  TraversalWrapper will attempt to get the variable and
	 * 		disregard any PHP errors or exceptions that are generated in the
	 * 		process.
	 *
	 * @param string $name The name of the variable to retrieve.
	 * @return TraversalWrapper the resulting variable, wrapped in its own
	 * 		instance of TraversalWrapper.
	 * @throws NoSuchVariableException if the given variable could not be
	 * 		found, and $nullIfNotFound was not specified or set to false in the
	 * 		constructor.
	 */
	public function __get($name) {
		$this->traversed[] = $name;
		if ($this->nullIfNotFound && is_null($this->var)) {
			return new TraversalWrapper($this->var, $this->nullIfNotFound,
				$this->traversed);
		}
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
			// The requested variable might be available through the __get()
			// magic method.  This tests for that without generating errors.
			set_error_handler(array($this, 'fieldNotFoundHandler'));
			try {
				$result = $this->var->$name;
			}
			catch (\Exception $e) {}
			restore_error_handler();
			if (isset($result)) {
				return new TraversalWrapper($result, $this->nullIfNotFound,
					$this->traversed);
			}
		}
		if ($this->nullIfNotFound) {
			$var = null;
			return new TraversalWrapper($var, $this->nullIfNotFound,
				$this->traversed);
		}
		$varName = implode('.', $this->traversed);
		$e = new NoSuchVariableException(
			'Variable does not exist in context: "' . $varName . '".');
		$e->variable = $varName;
		throw $e;
	}

	/**
	 * Attempts to set a specified value within the currently wrapped variable.
	 * This function is a PHP magic method and should not be called directly.
	 *
	 * @param string $name The name of the variable to be set.
	 * @param mixed $value The value to which the variable should be set.
	 * @throws NoSuchVariableException if the specified variable cannot be
	 * 		set because this TraversalWrapper does not contain a class or
	 * 		array.
	 */
	public function __set($name, $val) {
		if (is_array($this->var))
			$this->var[$name] = $val;
		else if (is_object($this->var))
			$this->var->$name = $val;
		else {
			throw new NoSuchVariableException('Cannot set member variable "' .
				$name . '" on a non-object/non-array.');
		}
	}

	/**
	 * Checks to see if the given variable is set within the currently wrapped
	 * variable.  This function is a PHP magic method and should not be called
	 * directly.
	 *
	 * @param string $name The name of the variable for which to check.
	 * @return boolean true if the variable exists, false if it does not or
	 * 		could not be detected.
	 */
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

	/**
	 * Unsets (deletes) a member variable or array key from the currently
	 * wrapped variable.  This function is a PHP magic method and should not be
	 * called directly.
	 *
	 * @param string $name The name of the variable to unset.
	 */
	public function __unset($name) {
		if (is_array($this->var) && isset($this->var[$name]))
			unset($this->var[$name]);
		else if (isset($this->var->$name))
			unset($this->var->$name);
	}

	/**
	 * Converts the contained variable to a string.  This function is a PHP
	 * magic method and should not be called directly.
	 *
	 * @return string The string representation of the wrapped variable.
	 */
	public function __toString() {
		return $this->var;
	}
	
	/**
	 * Handles errors from the __get magic method, when the method attempts to
	 * blindly access a member of the wrapped variable.  This handler simply
	 * throws an Exception, which is a clever way of forcing PHP to return to
	 * the originally executing function even when a core PHP error is
	 * encountered.
	 */
	protected function fieldNotFoundHandler($errno, $errstr, $errfile,
			$errline) {
		throw new \Exception();
	}
}

?>