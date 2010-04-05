<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\log\engines;

use hydrogen\log\Log;
use hydrogen\log\LogEngine;
use hydrogen\log\exceptions\InvalidPathException;
use hydrogen\config\Config;

class TextFileEngine implements LogEngine {
	
	protected $logfile, $fp;
	
	public function __construct() {
		$logdir = Config::getVal('log', 'logdir');
		$prefix = Config::getVal('log', 'fileprefix', false) ?: 'log';
		$filename = $prefix . date('ymd') . '.log';
			
		// If the path is relative, attempt to make it relative from the
		// config file path
		if ($this->isRelativePath($logdir)) {
			if (!$this->isRelativePath(Config::getConfigPath()))
				$logdir = dirname(Config::getConfigPath()) . DIRECTORY_SEPARATOR . $logdir;
			else
				throw new InvalidPathException("The config file path must be absolute in order to use a relative path for the log file.");
		}
		
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
	
	protected function isRelativePath($path) {
		if (DIRECTORY_SEPARATOR === '\\') {
			// Windows filesystem-specific
			$char = (int)$path[0];
			if ((($char >= (int)'a' && $char <= (int)'z') ||
				($char >= (int)'A' && $char <= (int)'Z')) &&
				$path[1] === ':' && $path[2] === DIRECTORY_SEPARATOR)
				return false;
		}
		if ($path[0] === DIRECTORY_SEPARATOR)
			return false;
		return true;
	}
}

?>