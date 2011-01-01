<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\semaphore\engines;

use hydrogen\semaphore\SemaphoreEngine;
use hydrogen\cache\CacheEngineFactory;

class CacheEngine extends SemaphoreEngine {
	protected $engine;
	
	public function __construct() {
		parent::__construct();
		$this->engine = CacheEngineFactory::getEngine();
	}
	
	public function acquire($sem_name, $wait_time=2) {
		if (isset($this->acquired_sems[$sem_name]) && $this->acquired_sems[$sem_name])
			return true;
		$key = "SEMLOCK:$sem_name";
		$start_time = time();
		if ($wait_time <= 0)
			$this->acquired_sems[$sem_name] = $this->engine->add($key, 1, $wait_time);
		else {
			while (!($this->acquired_sems[$sem_name] = $this->engine->add($key, 1, $wait_time))) {
				if (time() - $start_time > $wait_time)
					break;
				usleep(20);
			}
		}
		return $this->acquired_sems[$sem_name];
	}
	
	public function release($sem_name) {
		if (!isset($this->acquired_sems[$sem_name]) ||
				(isset($this->acquired_sems[$sem_name]) && !$this->acquired_sems[$sem_name]))
			return false;
		$key = "SEMLOCK:$sem_name";
		$this->engine->delete($key);
		$this->acquired_sems[$sem_name] = false;
		return true;
	}
}

?>