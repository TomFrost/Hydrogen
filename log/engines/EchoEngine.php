<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\log\engines;

use hydrogen\log\Log;
use hydrogen\log\LogEngine;

class EchoEngine implements LogEngine {

	public function write($loglevel, $file, $line, $msg) {
		switch ($loglevel) {
			case Log::LOGLEVEL_ERROR:
				$level = 'ERROR';
				break;
			case Log::LOGLEVEL_WARN:
				$level = 'WARN';
				break;
			case Log::LOGLEVEL_NOTICE:
				$level = 'NOTICE';
				break;
			case Log::LOGLEVEL_INFO:
				$level = 'INFO';
				break;
			case Log::LOGLEVEL_DEBUG:
				$level = 'DEBUG';
				break;
			default:
				$level = 'UNKNOWN';
		}
		$date = date("Y-m-d H:i:s");
		echo "[$date] [$level] ($file:$line): $msg\n";
	}

}

?>