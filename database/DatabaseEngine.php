<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database;

/**
 * The DatabaseEngine abstract is modeled directly after PDO for two reasons; first, it makes it
 * extremely easy to write PDO-based extensions, and second, any programmer familiar with PDO
 * should be able to pick up and starting working with Hydrogen\Database without much of a learning
 * curve.
 *
 * All Database engines for use with Hydrogen should extend this class and be placed in
 * hydrogen/database/engines, within the hydrogen\database\engines namespace.  As long as
 * those three things are done, the engine can be selected and used from the main config file.
 *
 * @link http://www.php.net/manual/en/class.pdo.php
 */
abstract class DatabaseEngine {
	/**
	 * DatabaseEngine uses {@link hydrogen\database\formatters\StandardSQLFormatter} to
	 * construct queries by default.  This may be overridden.
	 */
	const QUERY_FORMATTER = '\hydrogen\database\formatters\StandardSQLFormatter';
	protected $reconstruct;
	
	/**
	 * Opens a transaction session with the database.  Once this is called, no database-altering
	 * queries should actually take place until {@link #commit} is called.  The same queries
	 * can be canceled by calling {@link #rollBack}.
	 *
	 * @link http://www.php.net/manual/en/pdo.begintransaction.php
	 * @return boolean <code>true</code> if successful; <code>false</code> otherwise.
	 */
	abstract public function beginTransaction();
	
	/**
	 * Commits all queries that were stored since {@link #beginTransaction} was called.
	 *
	 * @link http://www.php.net/manual/en/pdo.commit.php
	 * @return boolean <code>true</code> if successful; <code>false</code> otherwise.
	 */
	abstract public function commit();
	
	/**
	 * Retrieves a PDO-similar error code for the last query made with the engine object
	 * (but not from a {@link DatabaseStatement} interaction).  While this number is
	 * wholly dependent on the specific DatabaseEngine being used, it should follow PDO's
	 * SQLSTATE as closely as possible and always return 0 or some form of 0 when there has
	 * been no error.
	 * 
	 * @link http://www.php.net/manual/en/pdo.errorcode.php
	 * @return mixed The error code if there was an error; 0 if no error; <code>null</code>
	 * 		if no query has been executed directly from the engine.
	 */
	abstract public function errorCode();
	
	/**
	 * Retrieves an array with deeper info about the last error.  The structure of the array is:
	 * 
	 * <code>[0] => SQLSTATE-similar error code available by caling {@link #errorCode}.
	 * [1] => Error code specific to the driver or engine being used.
	 * [2] => Human-readable error message specific to the driver or engine being used.</code>
	 *
	 * @link http://www.php.net/manual/en/pdo.errorinfo.php
	 * @return array An array of error information for errors specific to queries executed directly
	 * 		through the engine.  If no queries have been executed through the engine object,
	 * 		elements 1 and 2 of the array will be NULL.
	 */
	abstract public function errorInfo();
	
	/**
	 * Executes the provided SQL statement and returns the number of affected rows.  This
	 * method cannot be used to get the results of a SELECT statement.
	 *
	 * @link http://www.php.net/manual/en/pdo.exec.php
	 * @param string statement The SQL statement to be executed.
	 * @return int The number of rows affected by the query.
	 */
	abstract public function exec($statement);
	
	/**
	 * Gets the ID of the last row inserted into the database during this connection.
	 * 
	 * Note that not all DatabaseEngines use drivers that handle this function properly.
	 * As such, this function should only be used in the case where the programmer can be
	 * assured which DatabaseEngine will be used, and that it will not utilize a persistent
	 * connection.
	 *
	 * @link http://www.php.net/manual/en/pdo.lastinsertid.php
	 * @param string name The name of the sequence object for which the ID should be obtained.
	 * 		Optional, depending on the DatabaseEngine being used.
	 * @return mixed The ID of the last inserted row during this database connection.
	 */
	abstract public function lastInsertId($name=false);
	
	/**
	 * Prepares an SQL query for execution with {@link DatabaseStatement#execute}.
	 * 
	 * DatabaseEngines use the same prepared query format as many other popular database
	 * modules, including PDO.  Unlike traditional (and dated) query methods, prepared
	 * queries are immune to SQL injection if used properly.
	 * 
	 * Any dynamic element in an SQL query should be replaced with a question mark (?) or
	 * a variable name preceded by a colon (:varname) -- but only one type of replacement
	 * within the same query.  Note, however, that only variable values can be replaced.
	 * For example, the following are legal, preparable queries:
	 * 
	 * <code>SELECT customer FROM orders WHERE total > ? AND product = ?
	 * INSERT INTO orders (customer, product, total) VALUES (:cust, :prod, :total)</code>
	 * 
	 * These queries, however, are not legal and cannot be prepared:
	 * <code>SELECT customer FROM orders WHERE total > ? AND product = :prod
	 * SELECT ? FROM orders LIMIT 1</code>
	 * 
	 * The exception to this rule is if a query is prepared using a {@link Query}
	 * object, which supports both replacement types simultaneously.
	 *
	 * Once prepared, the values for the replaced items can be passed in when the
	 * query is executed.  This method returns a {@link DatabaseStatement} object,
	 * and the prepared query can be executed with {@link DatabaseStatement#execute}.
	 *
	 * @link http://www.php.net/manual/en/pdo.prepare.php
	 * @param string statement A legal and preparable SQL query.
	 * @throws InvalidSQLException If the statement cannot be processed.
	 * @return DatabaseStatement A prepared, ready-for-execution DatabaseStatement.
	 */
	abstract public function prepare($statement);
	
	/**
	 * Executes a valid SQL query and returns the results in a {@link DatabaseStatement}.
	 *
	 * @link http://www.php.net/manual/en/pdo.query.php
	 * @param string statement A legal and complete SQL query.
	 * @throws InvalidSQLException If the statement cannot be processed.
	 * @return DatabaseStatement An executed statement with results ready to read.
	 */
	abstract public function query($statement);
	
	/**
	 * Properly quotes a string for use in an SQL query.  Like <code>addslashes()</code>, but
	 * should use the quoting available in the database driver.  If such a method is not available,
	 * this function will use <code>addslashes()</code>.
	 *
	 * Note that this function should never be used except in EXTREMELY special cases.  Any time
	 * one needs to quote a string, a prepared query should be used.
	 *
	 * @link http://www.php.net/manual/en/pdo.quote.php
	 * @param string string The string to be SQL-safe quoted and escaped.
	 * @return string An SQL-safe string.
	 */
	abstract public function quote($string);
	
	/**
	 * Rolls back all queries that were stored since {@link #beginTransaction} was called.
	 *
	 * @link http://www.php.net/manual/en/pdo.rollback.php
	 * @return boolean <code>true</code> if successful; <code>false</code> otherwise.
	 */
	abstract public function rollBack();
	
	/**
	 * Immediately sets the engine's database connection to the one specified, disconnecting the current
	 * connection if necessary.  This should only be called in rare cases.  All new connections should
	 * be made by requesting a new engine from {@link DatabaseEngineFactory#getEngine}.
	 *
	 * @param string|boolean host Hostname to connect to, or <code>false</code> to use a UNIX socket.
	 * @param int|boolean port Port to connect to, or <code>false</code> to use a UNIX socket.
	 * @param string|boolean socket Socket to connect to, or <code>false</code> to use a hostname
	 * 		and port.
	 * @param string database The name of the database to connect to.
	 * @param string|boolean username The username to connect with, or <code>false</code> if no
	 * 		username is required.
	 * @param string|boolean password The password to connect with, or <code>false</code> if no
	 * 		password is required.
	 * @param string|boolean tablePrefix The prefix for tables, or <code>false</code> if no prefix
	 *		is required
	 * @throws hydrogen\database\exceptions\DatabaseConnectionException if a connection could not
	 * 		be made.
	 */
	abstract protected function setConnection($host, $port, $socket, $database, $username, $password, $tablePrefix);
	
	/**
	 * Gets an array of information needed to reconstruct this DatabaseEngine.  This is the same array
	 * used to restore the DatabaseEngine after being serialized/unserialized.
	 *
	 * The following keys are available in the associative array:
	 * host, port, socket, database, username, password, engine
	 *
	 * @return array An associative array containing all the information necessary to recreate this
	 * 		engine and its connection.
	 */
	public function getReconstructArray() {
		return $this->reconstruct;
	}
	
	/**
	 * Gets the table prefix that this engine was initialized with.  The prefix itself does
	 * not affect the function of the DatabaseEngine, but is stored as a convenience for any
	 * methods that may want to automatically prepend the user-chosen prefix to table names.
	 *
	 * @return string The prefix that friendly libraries should prepend to any table names, or
	 * 		an empty string if no prefix was set for this engine.
	 */
	public function getTablePrefix() {
		return $this->reconstruct['table_prefix'] ?: '';
	}
	
	/**
	 * Creates a new instance of this DatabaseEngine.  This should never be called directly;
	 * rather, new database engine instances should be requested by calling
	 * {@link DatabaseEngineFactory#getEngine} OR . {@link DatabaseEngineFactory#getEngineByName} 
	 * The DatabaseEngine is connected when created.
	 *
	 * @param string|boolean host Hostname to connect to, or <code>false</code> to use a UNIX socket.
	 * @param int|boolean port Port to connect to, or <code>false</code> to use a UNIX socket.
	 * @param string|boolean socket Socket to connect to, or <code>false</code> to use a hostname
	 * 		and port.
	 * @param string database The name of the database to connect to.
	 * @param string|boolean username The username to connect with, or <code>false</code> if no
	 * 		username is required.
	 * @param string|boolean password The password to connect with, or <code>false</code> if no
	 * 		password is required.
	 * @param string|boolean tablePrefix The prefix for tables, or <code>false</code> if no prefix
	 *		is required.
	 * @throws hydrogen\database\exceptions\DatabaseConnectionException if a connection could not
	 * 		be made.
	 */
	public function __construct($host, $port, $socket, $database, $username, $password, $tablePrefix) {
		$this->reconstruct = array(
			'host' => $host,
			'port' => $port,
			'socket' => $socket,
			'database' => $database,
			'username' => $username,
			'password' => $password,
			'engine' => get_class($this),
			'table_prefix' => $tablePrefix
			);
		$this->setConnection($host, $port, $socket, $database, $username, $password, $tablePrefix);
	}
	
	public function __sleep() {
		return array('reconstruct');
	}
	
	public function __wakeup() {
		$this->setConnection(
			$this->reconstruct['host'],
			$this->reconstruct['port'],
			$this->reconstruct['socket'],
			$this->reconstruct['database'],
			$this->reconstruct['username'],
			$this->reconstruct['password'],
			$this->reconstruct['table_prefix']
			);
	}
}

?>