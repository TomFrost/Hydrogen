<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\semaphore;

abstract class SemaphoreEngine {
	protected $acquired_sems;
	
	protected function __construct() {
		if (!isset($this->acquired_sems) || !is_array($this->acquired_sems))
			$this->acquired_sems = array();
	}
	
	function __destruct() {
		foreach ($this->acquired_sems as $sem)
			$this->release($sem);
	}
	
	abstract public function acquire($sem_name, $wait_time=2);
	abstract public function release($sem_name);
}

?>