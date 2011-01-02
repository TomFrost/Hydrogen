<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\log;

use hydrogen\config\Config;

class Log {
	const LOGLEVEL_ERROR = 1;
	const LOGLEVEL_WARN = 2;
	const LOGLEVEL_NOTICE = 3;
	const LOGLEVEL_INFO = 4;
	const LOGLEVEL_DEBUG = 5;
	
	protected static $log = false;
	protected $engine, $maxlevel;
	
	protected function __construct() {
		$class = '\\' . __NAMESPACE__ . '\engines\\' . (Config::getVal('log', 'engine') ?: 'No') . 'Engine';
		$this->engine = new $class();
		$this->maxlevel = Config::getVal('log', 'loglevel', false) ?: 1;
	}
	
	protected static function getInstance() {
		if (!static::$log)
			static::$log = new Log();
		return static::$log;
	}
	
	protected static function write($loglevel, $msg, $file=false, $line=false) {
		$logger = static::getInstance();
		if ($loglevel <= $logger->maxlevel) {
			if (!$file || !$line)
				$trace = debug_backtrace();
			$file = $file ?: basename($trace[1]['file']);
			$line = $line ?: $trace[1]['line'];
			if (isset($trace))
				unset($trace);
			return $logger->engine->write($loglevel, $file, $line, $msg);
		}
		return false;
	}
	
	public static function error($msg, $file=false, $line=false) {
		return static::write(static::LOGLEVEL_ERROR, $msg, $file, $line);
	}
	
	public static function warn($msg, $file=false, $line=false) {
		return static::write(static::LOGLEVEL_WARN, $msg, $file, $line);
	}
	
	public static function notice($msg, $file=false, $line=false) {
		return static::write(static::LOGLEVEL_NOTICE, $msg, $file, $line);
	}
	
	public static function info($msg, $file=false, $line=false) {
		return static::write(static::LOGLEVEL_INFO, $msg, $file, $line);
	}
	
	public static function debug($msg, $file=false, $line=false) {
		return static::write(static::LOGLEVEL_DEBUG, $msg, $file, $line);
	}
}

?>