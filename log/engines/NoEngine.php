<?php
namespace hydrogen\log\engines;

use hydrogen\log\LogEngine;

class NoEngine implements LogEngine {
	
	public function write($loglevel, $file, $line, $msg) {
		return false;
	}
}

?>