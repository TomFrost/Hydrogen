<?php
namespace hydrogen\semaphore;

use \hydrogen\config\Config;

class SemaphoreEngineFactory {
	protected static $engine = NULL;
	
	private function __construct() {}
	
	public static function getEngine() {
		if (is_null(static::$engine)) {
			$engineClass = '\hydrogen\semaphore\engines\\' . Config::getVal('semaphore', 'engine') . 'Engine';
			static::$engine = new $engineClass();
		}
		return static::$engine;
	}
}

?>