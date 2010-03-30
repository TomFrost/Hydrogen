<?php
namespace hydrogen\semaphore\engines;

use \hydrogen\semaphore\SemaphoreEngine;

class NoEngine extends SemaphoreEngine {
	public function acquire($sem_name, $wait_time=2) {
		return true;
	}
	
	public function release($sem_name) {
		return true;
	}
}

?>