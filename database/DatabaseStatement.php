<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database;

/**
 * Like {@link DatabaseEngine}, DatabaseStatement is modeled strongly after PDO -- in this case,
 * PDOStatement.  The DatabaseStatement abstract defines an object that can hold and execute a
 * prepared statement as well as the result set once a query is executed.
 *
 * @link http://www.php.net/manual/en/class.pdostatement.php
 */
abstract class DatabaseStatement {
	
	/**
	 * Binds a given variable to the specified SELECT'ed column name or number.  The variable,
	 * then, will be updated every time {@link #fetchBound} is called with the fetched row's
	 * result.
	 *
	 * @example
	 * 	<code>use hydrogen\database\DatabaseEngineFactory;
	 * 	$engine = DatabaseEngineFactory::getEngine();
	 * 	$statement = $engine->prepare("SELECT order_id, customer, product FROM orders");
	 * 	$statement->execute();
	 *
	 * 	$statement->bindColumn(1, $orderId);
	 * 	$statement->bindColumn("customer", $cust);
	 * 	$statement->bindColumn("product", $prod);
	 *
	 * 	while ($statement->fetchBound())
	 * 		echo "Order: " . $orderId . " " . $cust . " " . $prod;</code>
	 *
	 * @link http://www.php.net/manual/en/pdostatement.bindcolumn.php
	 * @param int|string column The name or number of the SELECT query column to bind to.
	 * @param var param The variable to bind to the specified column's current result.
	 * @return <code>true</code> if successful; <code>false</code> otherwise.
	 */
	abstract public function bindColumn($column, &$param);
	
	/**
	 * Binds a variable to a query parameter (by name or number) before
	 * an execution.
	 *
	 * @link http://www.php.net/manual/en/pdostatement.bindparam.php
	 */
	abstract public function bindParam($param, &$variable);
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.bindvalue.php
	 */
	abstract public function bindValue($param, $value);
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.closecursor.php
	 */
	abstract public function closeCursor();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.columncount.php
	 */
	abstract public function columnCount();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.errorcode.php
	 */
	abstract public function errorCode();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.errorinfo.php
	 */
	abstract public function errorInfo();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.execute.php
	 */
	abstract public function execute($inputParameters=array());
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.fetchall.php
	 */
	abstract public function fetchAll();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.fetchassoc.php
	 */
	abstract public function fetchAssoc();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.fetchbound.php
	 */
	abstract public function fetchBound();
	
	/**
	 * Fetches the results into an existing object.  This is an analogue
	 * of PDO's FETCH_INTO fetch mode.
	 *
	 * @link http://php.net/manual/en/pdostatement.setfetchmode.php
	 */
	abstract public function fetchInto($object);
	
	/**
	 * Fetches the results into a new instance of a class.  This is an analogue
	 * of PDO's FETCH_CLASS fetch mode.
	 *
	 * @link http://php.net/manual/en/pdostatement.setfetchmode.php
	 */
	abstract public function fetchIntoNew($classname, $ctorargs=array());
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.fetchobject.php
	 */
	abstract public function fetchObject();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.nextrowset.php
	 */
	abstract public function nextRowset();
	
	/**
	 * 
	 *
	 * @link http://www.php.net/manual/en/pdostatement.rowcount.php
	 */
	abstract public function rowCount();
	
	
	/**
	 * Alias of {@link #fetchObject}.
	 *
	 * @see #fetchObject 
	 */
	public function fetchObj() {
		return $this->fetchObject();
	}
	
	public function __destruct() {
		$this->closeCursor();
	}
}

?>