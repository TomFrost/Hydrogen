<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\model;

use hydrogen\database\DatabaseEngineFactory;
use hydrogen\recache\RECacheManager;
use hydrogen\model\exceptions\NoSuchMethodException;
use hydrogen\log\Log;

abstract class Model {
	protected static $instances = array();
	protected $dbengine, $cm;
	
	protected function __construct($dbengine) {
		$this->dbengine = $dbengine;
		$this->cm = RECacheManager::getInstance();
	}
	
	public static function getInstance($dbengine=false) {
		$class = get_called_class();
		if (is_string($dbengine))
			$dbengine = DatabaseEngineFactory::getEngineByName($dbengine);
		else
			$dbengine =  $dbengine ?: DatabaseEngineFactory::getEngine();
		$hash = spl_object_hash($dbengine);
		if (!isset(static::$instances[$class]))
			static::$instances[$class] = array();
		if (!isset(static::$instances[$class][$hash]))
			static::$instances[$class][$hash] = new $class($dbengine);
		return static::$instances[$class][$hash];
	}
	
	public function __call($func, $args) {
		$methods = get_class_methods($this);
		$valids = array();
		$useCache = false;
		if (strrpos($func, 'Cached') === strlen($func) - 6) {
			$useCache = true;
			$func = substr($func, 0, -6);
		}
		$func .= '__';
		foreach($methods as $method) {
			if (strpos($method, $func) === 0) {
				if (!$useCache)
					return call_user_func_array(array($this, $method), $args);
				else {
					$data = explode('_', substr($method, strlen($func)));
					$ttl = isset($data[0]) ? $data[0] : 300;
					$groups = array();
					for ($i = 1; $i < count($data); $i++)
						$groups[] = &$data[$i];
					$key = (isset(static::$modelID) ? static::$modelID : get_class($this)) . '_' . substr($func, 0, -2);
					foreach($args as $arg)
						$key .= '_' . (is_bool($arg) ? ($arg ? '1' : '0') : $arg);
					$gstring = print_r($groups, true);
					return $this->cm->recache_get($key, $ttl, $groups, array($this, $method), $args);
				}
			}
		}
		$class = get_class($this);
		throw new NoSuchMethodException("Method $func does not exist in model $class.");
	}
}