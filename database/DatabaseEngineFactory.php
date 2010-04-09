<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
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
	 * Creates a new instance of this DatabaseEngine if the specified connection is new, or returns
	 * the stored engine for a connection that an engine's already been instantiated for.  The
	 * DatabaseEngine is connected as soon as it is created.
	 *
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
	 * @param string|boolean engine The name of the engine to use for this connection, or
	 * 		<code>false</code> to use the engine defined in the Config object.
	 * @param string|boolean tablePrefix The prefix for tables, or <code>false</code> if no prefix
	 *		is required
	 * @throws hydrogen\database\exceptions\DatabaseConnectionException if a connection could not
	 * 		be made.
	 * @return DatabaseEngine The requested DatabaseEngine with a connection to the specified
	 * 		database server.
	 */
	public static function getEngine($host=false, $port=false, $socket=false,
                        $database=false, $username=false, $password=false, $engine=false, $tablePrefix=false) {
                $dbSelectKey = Config::getRequiredVal('database','engine');
                if(is_array($dbSelectKey)){
                        $dbSelectKey = array_keys($dbSelectKey);
                        $dbSelect = $dbSelectKey[0];
                }
                else 
                        $dbSelect = false;
                $host = $host ?: Config::getVal('database', 'host', $dbSelect);
                $port = $port ?: Config::getVal('database', 'port', $dbSelect);
                $socket = $socket ?: Config::getVal('database', 'socket', $dbSelect);
                $database = $database ?: Config::getVal('database', 'database', $dbSelect);
                $username = $username ?: Config::getVal('database', 'username', $dbSelect);
                $password = $password ?: Config::getVal('database', 'password', $dbSelect);
                $tablePrefix = $tablePrefix ?: Config::getVal('database', 'table_prefix', $dbSelect);
                $engine = $engine ?: Config::getRequiredVal('database', 'engine', $dbSelect);
                if (strpos($engine, 'hydrogen') !== false)
                        $engineClass = &$engine;
                else
                        $engineClass = '\hydrogen\database\engines\\' . $engine . 'Engine';
                $key = ($host ?: '') . ':' . ($port ?: '') . ':' . ($socket ?: '') . ':' . 
                        ($database ?: '') . ':' . ($username ?: '') . ':' . ($engineClass ?: '') . ':' . ($tablePrefix ?: '');
                if (!isset(static::$engine[$key])) {
                        static::$engine[$key] = new $engineClass(
                                $host ?: Config::getVal('database', 'host', $dbSelect),
                                $port ?: Config::getVal('database', 'port', $dbSelect),
                                $socket ?: Config::getVal('database', 'socket', $dbSelect),
                                $database ?: Config::getVal('database', 'database', $dbSelect),
                                $username ?: Config::getVal('database', 'username', $dbSelect),
                                $password ?: Config::getVal('database', 'password', $dbSelect),
                                $tablePrefix ?: Config::getVal('database', 'table_prefix', $dbSelect);
                                );
                }
                return static::$engine[$key];
        }
	/**
	 * Creates a new instance of this DatabaseEngine if the specified connection is new, or returns
	 * the stored engine for a connection that an engine's already been instantiated for.  The
	 * DatabaseEngine is connected as soon as it is created.
	 *
	 * @param string dbSelect Subkey value to call for database configuration
	 * @param string|boolean tablePrefix The prefix for tables, or <code>false</code> to use prefix
	 *		defined in the Config object
	 * @throws hydrogen\database\exceptions\DatabaseConnectionException if a connection could not
	 * 		be made.
	 * @return DatabaseEngine The requested DatabaseEngine with a connection to the specified
	 * 		database server.
	 */
        public static function getEngineByName($dbSelect, $tablePrefix=false) {
                $host = Config::getVal('database', 'host', $dbSelect);
                $port = Config::getVal('database', 'port', $dbSelect);
                $socket = Config::getVal('database', 'socket', $dbSelect);
                $database = Config::getVal('database', 'database', $dbSelect);
                $username = Config::getVal('database', 'username', $dbSelect);
                $password = Config::getVal('database', 'password', $dbSelect);
                $tablePrefix = $tablePrefix ?: Config::getVal('database', 'table_prefix', $dbSelect);
                $engine = Config::getRequiredVal('database', 'engine', $dbSelect);
                if (strpos($engine, 'hydrogen') !== false)
                        $engineClass = &$engine;
                else
                        $engineClass = '\hydrogen\database\engines\\' . $engine . 'Engine';
                $key = ($host ?: '') . ':' . ($port ?: '') . ':' . ($socket ?: '') . ':' . 
                        ($database ?: '') . ':' . ($username ?: '') . ':' . ($engineClass ?: '') . ':' . ($tablePrefix ?: '');
                if (!isset(static::$engine[$key])) {
                        static::$engine[$key] = new $engineClass(
                                $host ?: Config::getVal('database', 'host', $dbSelect),
                                $port ?: Config::getVal('database', 'port', $dbSelect),
                                $socket ?: Config::getVal('database', 'socket', $dbSelect),
                                $database ?: Config::getVal('database', 'database', $dbSelect),
                                $username ?: Config::getVal('database', 'username', $dbSelect),
                                $password ?: Config::getVal('database', 'password', $dbSelect),
                                $tablePrefix ?: Config::getVal('database', 'table_prefix', $dbSelect);
                                );
                }
                return static::$engine[$key];
        }
}

?>