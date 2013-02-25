<?php
/*
 * Copyright (c) 2009 - 2013, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database\engines;

use hydrogen\database\engines\PDOEngine;
use hydrogen\database\exceptions\DatabaseConnectionException;

class PostgresqlPDOEngine extends PDOEngine {

	public function setConnection($host, $port, $socket, $database, $username, $password, $tablePrefix) {
		if ($socket)
			throw new DatabaseConnectionException('PostgreSQL does not support socket connections');
		else
			parent::setPDOConnection($host, $port, $socket, $database, $username, $password,
				"pgsql:host=$host;port=$port;dbname=$database");
	}
}

?>