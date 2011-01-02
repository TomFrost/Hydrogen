<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database;

use hydrogen\config\Config;

/**
 * The DatabaseEngineFactory is responsible for creating and maintaining DatabaseEngines
 * and their connections.  DatabaseEngineFactory ensures that each engine is a singleton,
 * creating a new one only when there's a new connection to be made.
 */
class DatabaseEngineFactory {
	/**
	 * An associative array of DatabaseEngines.
	 * @var array
	 */
	protected static $engine = array();
	
	/**
	 * This object should not be instantiated.
	 */
	private function __construct() {}
	
	/**
	 * Creates a DatabaseEngine with the specified connection parameters.  If a DatabaseEngine
	 * was created using the exact same parameters provided any time earlier in this page request,
	 * that same DatabaseEngine will be returned rather than creating a new one.
	 *
	 * @param string engineName The name of the engine to use for this connection.
	 * @param string|boolean host Hostname to connect to, or <code>false</code> to use a the
	 * 		hostname defined in the Config object.
	 * @param int|boolean port Port to connect to, or <code>false</code> to use the port defined
	 * 		in the Config object.
	 * @param string|boolean socket Socket to connect to, or <code>false</code> to use the socket
	 * 		defined in the Config object.
	 * @param string|boolean database The name of the database to connect to, or <code>false</code>
	 * 		to use the database defined in the Config object.
	 * @param string|boolean username The username to connect with, or <code>false</code> to
	 * 		use the username defined in the Config object.
	 * @param string|boolean password The password to connect with, or <code>false</code> to
	 * 		use the password defined in the Config object.
	 * @param string|boolean tablePrefix The prefix for tables, or <code>false</code> if no prefix
	 *		is required
	 * @throws hydrogen\database\exceptions\DatabaseConnectionException if a connection could not
	 * 		be made.
	 * @return DatabaseEngine The requested DatabaseEngine with a connection to the specified
	 * 		database server.
	 */
	public static function getCustomEngine($engineName, $host=false, $port=false, $socket=false,
			$database=false, $username=false, $password=false, $tablePrefix=false) {
		if (strpos($engineName, 'hydrogen') !== false)
			$engineClass = &$engineName;
		else
			$engineClass = '\hydrogen\database\engines\\' . $engineName . 'Engine';
			
		$key = $host . ':' . $port . ':' . $socket . ':' . $database . ':' . $username .
			':' . $engineClass  . ':' . $tablePrefix;
		
		if (!isset(static::$engine[$key])) {
			static::$engine[$key] = new $engineClass($host, $port, $socket, $database,
				$username, $password, $tablePrefix);
		}
		return static::$engine[$key];
	}
	
	/**
	 * Creates a new instance of DatabaseEngine if the specified connection is new, or returns
	 * the stored engine for a connection that an engine's already been instantiated for.  The
	 * DatabaseEngine is connected as soon as it is created.
	 * 
	 * If no dbConfigName is specified, the database configuration from the
	 * {@link \hydrogen\config\Config} object is used.  If there are multiple database configurations
	 * in the Config object, the first one defined will be used here.
	 *
	 * If multiple database configurations have been specified in the Config object, the sub-key of
	 * the appropriate configuration can be passed in for dbConfigName.  For example:
	 * <pre>
	 * [database]
	 * host[primary] = localhost
	 * port[primary] = 3306
	 * socket[primary] = 
	 * database[primary] = myDB
	 * username[primary] = myDBUser
	 * password[primary] = myDBPass
	 * table_prefix[primary] = myApp_
	 *
	 * host[backup] = backup.mydomain.com
	 * port[backup] = 3306
	 * socket[backup] = 
	 * database[backup] = backupDB
	 * username[backup] = myDBUser
	 * password[backup] = myDBPass
	 * table_prefix[backup] = myApp_
	 * </pre>
	 * Using this configuration, this function could be called with either "primary" or "backup"
	 * as the dbConfigName argument to get the appropriate DatabaseEngine.  If no dbConfigName
	 * is specified, the "primary" sub-key will be used since it appears first in the file.
	 *
	 * @param string|boolean dbConfigName OPTIONAL: The sub-key of the engine configuration to
	 * 		pull from the {@link \hydrogen\config\Config} object.  If false or unspecified, the
	 * 		first (or only) database configuration is pulled from Config.
	 * @throws hydrogen\database\exceptions\DatabaseConnectionException if a connection could not
	 * 		be made.
	 * @return DatabaseEngine The requested DatabaseEngine with a connection to the specified
	 * 		database server.
	 */
	public static function getEngine($dbConfigName=false) {
		if ($dbConfigName === false) {
			$engines = Config::getRequiredVal('database','engine');
			if (is_array($engines)) {
				$engines = array_keys($engines);
				$dbConfigName = $engines[0];
			}
		}
			
		return static::getCustomEngine(
			Config::getRequiredVal('database', 'engine', $dbConfigName),
			Config::getVal('database', 'host', $dbConfigName),
			Config::getVal('database', 'port', $dbConfigName),
			Config::getVal('database', 'socket', $dbConfigName),
			Config::getVal('database', 'database', $dbConfigName),
			Config::getVal('database', 'username', $dbConfigName),
			Config::getVal('database', 'password', $dbConfigName),
			Config::getVal('database', 'table_prefix', $dbConfigName)
			);
	}
}

?>