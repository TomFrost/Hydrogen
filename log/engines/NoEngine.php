<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\log\engines;

use hydrogen\log\LogEngine;

class NoEngine implements LogEngine {
	
	public function write($loglevel, $file, $line, $msg) {
		return false;
	}
}

?>