<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\TraversalWrapper;
use hydrogen\view\exceptions\NoSuchVariableException;

/**
 * The ContextStack is the container for the variables that are populated into
 * a {@link \hydrogen\view\ViewSandbox} and made available to any template
 * executed within it.
 *
 * The ContextStack also supports the concept of 'scope', with the abiltiy to
 * push and pop additional layers to the top of the stack.  At any time, a
 * ContextStack instance can be given the {@link push()} command.  Pushing the
 * stack adds a layer on top of the stack on which all new variables will be
 * defined.  Later, the stack can be popped with the {@link pop()} function
 * and that layer, and all the variables on it, will be removed from the
 * context as though they never existed.  Pre-existing variables that were
 * changed during this time, though, will retain their changes after the pop.
 *
 * In this way, the stack can be pushed at the beginning of a loop and popped
 * at the end to support private scope within the loop, while still allowing
 * access to variables defined outside of the loop.  Functions can have their
 * own private context, and blocks of code can be defined where new variables
 * are temporary.
 *
 * ContextStack includes convenient magic methods for getting and setting,
 * allowing variables to be retrieved/set/unset/etc by accessing them directly
 * as members of the instance.  For example, to set a new variable in the top
 * layer of the ContextStack, both of these lines do the exact same thing:
 *
 * <pre>
 * $context->set('username', 'Ted');
 * $context->username = 'Ted';
 * </pre>
 *
 * Getting a value through this method will return the variable wrapped in
 * an instance of {@link TraversalWrpper}.  See the documentation for
 * TraversalWrapper for the benefits that offers.  To get a value not wrapped
 * in that class, the actual {@link get()} method must be used.
 *
 * @see \hydrogen\view\TraversalWrapper
 */
class ContextStack {
	protected $stack;
	protected $stackLevel;

	/**
	 * Creates a new ContextStack, optionally with a set of initial variables
	 * with which to populate the context.
	 *
	 * @param array $initialData An optional associative array of key/value
	 * 		pairs to add to the context.
	 */
	public function __construct($initialData=false) {
		$this->stack = array(array());
		$this->stackLevel = array();
		if (is_array($initialData))
			$this->setArray($initialData);
	}
	
	/**
	 * Gets a specified key's value, wrapped in an instance of
	 * {@link TraversalWrapper}.  This is a PHP magic method and should
	 * not be called directly.  This function is an alias for
	 * {@link getWrapped()}.
	 *
	 * @param string $key The key name for which to retrieve the stored value.
	 * @return mixed the value that belongs to the specified key.
	 * @throws NoSuchVariableException If the requested key does not exist
	 * 		in this context.
	 */
	public function __get($key) {
		return $this->getWrapped($key);
	}
	
	/**
	 * Sets the specified key to the specified value.  This is a PHP magic
	 * method and should not be called directly.  This function is an alias for
	 * {@link set()}.
	 *
	 * @param string $key The key name to create or change in this context.
	 * 		If the key is new, it will be created on the top level of the
	 * 		stack.
	 * @param mixed $value The value to which the key should be set.
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}
	
	/**
	 * Checks to see if the specified key has been created in this context.
	 * This is a PHP magic method and should not be called directly.  This
	 * function is an alias for {@link keyExists()}.
	 *
	 * @param string $key The name of the key to check for in this context.
	 * @return boolean true if the key exists, false otherwiase.
	 */
	public function __isset($key) {
		return $this->keyExists($Key);
	}
	
	/**
	 * Deletes a key from this context.  This is a PHP magic method and should
	 * not be called directly.  This function is an alias for {@link delete()}.
	 *
	 * @param string $key The name of the key to delete from this context.
	 * @throws NoSuchVariableException If the specified key does not exist
	 * 		in this context.
	 */
	public function __unset($key) {
		$this->delete($key);
	}

	/**
	 * Pushes a new layer on top of the context stack.  Any new variables set
	 * will be "assigned" to this new layer, and can be instantly deleted by
	 * removing the layer with the {@link pop()} method.
	 */
	public function push() {
		array_push($this->stack, array());
	}

	/**
	 * Pops the topmost layer from the context stack, removing any variables
	 * declared on that layer from this context.  If this method is called
	 * without {@link push()} having been called first, this ContextStack will
	 * remain unchanged.
	 *
	 * @return ContextStack a new instance of ContextStack containing the
	 * 		variables from the popped layer, or an instance with no variables
	 * 		if there was no top layer to remove.
	 */
	public function pop() {
		return new ContextStack(array_pop($this->stack));
	}

	/**
	 * Sets the specified key to the specified value.  If the key is new, it
	 * will be created on the top layer of the stack.  If not, the key will be
	 * changed regardless of what layer it is on.
	 *
	 * @param string $key The name of the key to set.
	 * @param mixed $value The value to which the key should be set.
	 */
	public function set($key, $value) {
		$level = false;
		for ($i = count($this->stack) - 1; $i >= 0 ; $i--) {
			if (array_key_exists($key, $this->stack[$i])) {
				$level = $i;
				break;
			}
		}
		if ($level === false) {
			$level = count($this->stack) - 1;
			$this->stackLevel[$key] = $level;
		}
		$this->stack[$level][$key] = $value;
	}

	/**
	 * Sets an entire array of variables in this context.  Each new key will
	 * be set on the topmost layer of the context stack if it is new, and any
	 * existing keys will be changed regardless of the layer they are on.
	 *
	 * @param array $kvArray An associative array of keys and the values to
	 * 		which they should be set.
	 */
	public function setArray($kvArray) {
		foreach ($kvArray as $key => $value)
			$this->set($key, $value);
	}

	/**
	 * Gets the value for the specified key.
	 *
	 * @param string $key The key for which to retrieve the value.
	 * @param boolean $nullIfNotFound If true, NULL will be returned when the
	 * 		specified key does not exist in this context.  Otherwise, a
	 * 		{@link NoSuchVariableException} will be thrown in this case.  If
	 * 		not set, the default is false.
	 * @return mixed the value of the specified key, or NULL if
	 * 		$nullIfNotFound is true and the specified key does not exist
	 * 		in this context.
	 * @throws NoSuchVariableException if $nullIfNotFound is false and the
	 * 		specified key does not exist in this context.
	 */
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

	/**
	 * Gets the value for the specified key, wrapped in an instance of
	 * {@link TraversalWrapper}.  See the TraversalWrapper documentation for
	 * usage and the benefits this provides.
	 *
	 * @param string $key The key for which to retrieve the wrapped value.
	 * @param boolean $nullIfNotFound If true, NULL will be returned when the
	 * 		specified key does not exist in this context.  Otherwise, a
	 * 		{@link NoSuchVariableException} will be thrown in this case.  If
	 * 		not set, the default is false.  Note that this value will carry
	 * 		over into the {@link TraversalWrapper} instance and apply to all
	 * 		variables in the traversal chain.
	 * @return TraversalWrapper a new instance of {@link TraversalWrapper}
	 * 		wrapped around the value for the specified key, or wrapped around
	 * 		NULL if $nullIfNotFound is true and the specified key does not
	 * 		exist in this context.
	 * @throws NoSuchVariableException if $nullIfNotFound is false and the
	 * 		specified key does not exist in this context.
	 * @see \hydrogen\view\TraversalWrapper
	 */
	public function getWrapped($key, $nullIfNotFound=false) {
		$var = $this->get($key, $nullIfNotFound);
		$traversed = array($key);
		return new TraversalWrapper($var, $nullIfNotFound, $traversed);
	}

	/**
	 * Deletes the specified key from this context.
	 *
	 * @param string $key The key to delete from this context.
	 * @throws NoSuchVariableException if the specified key does not exist
	 * 		in this context.
	 */
	public function delete($key) {
		if (!$this->keyExists($key)) {
			$e = new NoSuchVariableException(
				"Variable does not exist in context: $key");
			$e->variable = $key;
			throw $e;
		}
		unset($this->stack[$this->stackLevel[$key]][$key]);
	}

	/**
	 * Checks to see if the specified key exists in this context.
	 *
	 * @param string $key The name of the key to check for in this context.
	 * @return boolean true if the key exists, false otherwise.
	 */
	public function keyExists($key) {
		return isset($this->stackLevel[$key]);
	}
}

?>