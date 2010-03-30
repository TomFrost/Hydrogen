<?php
namespace hydrogen\log;

interface LogEngine {
	public function write($loglevel, $file, $line, $msg);
}

?>