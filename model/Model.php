<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\model;

use hydrogen\common\MagicCacheable;

/**
 * Model provides a baseline for how Model classes should be constructed.
 * It offers no data manipulation functionality of its own, but includes
 * two patterns that should be used in every extending class: the singleton
 * pattern (letting only one instance of each Model-extending class exist) and
 * the MagicCacheable pattern (allowing the return value of functions to be
 * cached, based on what those functions are named).
 *
 * Model-extending classes should never be instantiated with the 'new' keyword.
 * Rather, an instance should be gotten with the {@link #getInstance} method,
 * like this:
 *
 * <pre>
 * $userModel = \myapp\UserModel::getInstance();
 * </pre>
 *
 * For instructions on how to name functions within the extended Model to
 * allow their result to be cached automatically, see
 * {@link hydrogen\common\MagicCacheable}.
 */
abstract class Model extends MagicCacheable {
	protected static $instances = array();
	
	/**
	 * This class should not be instantiated from the outside.
	 * Instead, call {@link #getInstance}.
	 */
	protected function __construct() { }
	
	/**
	 * Returns an instance of this Model singleton.  If no instance of this
	 * Model has been created, one will be created and returned.  Otherwise,
	 * the already created Model will be returned.
	 *
	 * @return An instance of this Model.
	 */
	public static function getInstance() {
		$class = get_called_class();
		if (!isset(static::$instances[$class]))
			static::$instances[$class] = new $class();
		return static::$instances[$class];
	}
	
}
