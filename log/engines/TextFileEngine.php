<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\log\engines;

use hydrogen\log\Log;
use hydrogen\log\LogEngine;
use hydrogen\config\Config;

class TextFileEngine implements LogEngine {
	
	protected $logfile, $fp;
	
	public function __construct() {
		$logdir = Config::getVal('log', 'logdir');
		$prefix = Config::getVal('log', 'fileprefix', false) ?: 'log';
		$filename = $prefix . date('ymd') . '.log';
			
		// Get our path relative to the config file
		$logdir = Config::getAbsolutePath($logdir);
		
		// Add the trailing slash if necessary
		if ($logdir[strlen($logdir) - 1] != DIRECTORY_SEPARATOR)
			$logdir .= DIRECTORY_SEPARATOR;
		$this->logfile = $logdir . $filename;
	}
	
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
		$str = "[$date] [$level] ($file:$line): $msg\n";
		$this->fp = @fopen($this->logfile, 'a');
		$success = false;
		if ($this->fp) {
			if (@flock($this->fp, LOCK_EX)) {
				@fwrite($this->fp, $str) && $success = true;
				@flock($this->fp, LOCK_UN);
			}
			@fclose($this->fp);
			unset($this->fp);
		}
		else
			throw new InvalidPathException("Could not write to " . $this->logfile);
	}
	
	public function __destruct() {
		if (isset($this->fp))
			@fclose($this->fp);
	}
}

?>