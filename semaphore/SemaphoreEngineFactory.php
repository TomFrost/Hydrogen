<?php
namespace hydrogen\semaphore;

use hydrogen\config\Config;

class SemaphoreEngineFactory {
	protected static $engine = NULL;
	
	private function __construct() {}
	
	public static function getEngine() {
		if (is_null(static::$engine)) {
			$engineName = Config::getVal('semaphore', 'engine', false) ?: 'No';
			$engineClass = "\\hydrogen\\semaphore\\engines\\${engineName}Engine";
			static::$engine = new $engineClass();
		}
		return static::$engine;
	}
}

?>