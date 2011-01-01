<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\cache;

use hydrogen\config\Config;

/**
 * CacheEngineFactory manages the creation of cache engines, making sure only one of each is created
 * during the lifetime of the request.
 */
class CacheEngineFactory {
	/**
	 * An array of created engines.
	 * @var array
	 */
	protected static $engine;
	
	/**
	 * This class should not be instantiated.
	 */
	private function __construct() {}
	
	/**
	 * Gets an instance of the specified cache engine.  If an instance has already been
	 * created for the specified engine, it will be returned.  Otherwise, a new instance
	 * will be made.
	 *
	 * @param string engine The name of the engine to instantiate or get.
	 * @return CacheEngine An instance of the specified engine.
	 */
	public static function getEngine($engine=false) {
		if (!isset(self::$engine))
			static::$engine = array();
		if (!$engine)
			$engine = Config::getVal('cache', 'engine');
		if (!$engine)
			$engine = 'No';
		if (!isset(static::$engine[$engine])) {
			$engineClass = '\hydrogen\cache\engines\\' . $engine . 'Engine';
			static::$engine[$engine] = new $engineClass();
		}
		return static::$engine[$engine];
	}
}

?>