<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database\engines;

use hydrogen\database\engines\PDOEngine;

class MysqlPDOEngine extends PDOEngine {
	
	public function setConnection($host, $port, $socket, $database, $username, $password, $tablePrefix) {
		if ($socket)
			parent::setPDOConnection($host, $port, $socket, $database, $username, $password,
				"mysql:unix_socket=$socket;dbname=$database");
		else
			parent::setPDOConnection($host, $port, $socket, $database, $username, $password,
				"mysql:host=$host;port=$port;dbname=$database");
	}
}

?>
