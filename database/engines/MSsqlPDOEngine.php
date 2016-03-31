<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database\engines;

use hydrogen\database\engines\PDOEngine;
use hydrogen\database\statements\GenericPDOStatement;

class MSsqlPDOEngine extends PDOEngine {

	const QUERY_FORMATTER = '\hydrogen\database\formatters\MSSQLFormatter';

	public function setConnection($host, $port, $socket, $database, $username, $password, $tablePrefix) {
		if($port)
			parent::setPDOConnection($host, $port, $socket, $database, $username, $password,
				"sqlsrv:server=$host,$port;Database = $database");
		else
			parent::setPDOConnection($host, $port, $socket, $database, $username, $password,
				"sqlsrv:server=$host;Database = $database");
	}
}

?>