<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database\engines;

use PDO;
use PDOException;
use hydrogen\database\DatabaseEngine;
use hydrogen\database\statements\PDOStatement;
use hydrogen\database\exceptions\DatabaseConnectionException;
use hydrogen\database\exceptions\InvalidSQLException;

abstract class PDOEngine extends DatabaseEngine {
	protected $pdo;
	
	protected function setPDOConnection($host, $port, $socket, $database, $username, $password, $dsn, $driverOptions=false) {
		try {
			if (isset($driverOptions) && $driverOptions)
				$this->pdo = new PDO($dsn, $username, $password, $driverOptions);
			else if (isset($password) && $password)
				$this->pdo = new PDO($dsn, $username, $password);
			else if (isset($username) && $username)
				$this->pdo = new PDO($dsn, $username);
			else
				$this->pdo = new PDO($dsn);
		}
		catch (PDOException $e) {
			throw new DatabaseConnectionException($e->getMessage());
		}
	}
	
	public function beginTransaction() {
		return $this->pdo->beginTransaction();
	}
	
	public function commit() {
		return $this->pdo->commit();
	}
	
	public function errorCode() {
		return $this->pdo->errorCode();
	}
	
	public function errorInfo() {
		return $this->pdo->errorInfo();
	}
	
	public function exec($statement) {
		try {
			return new PDOStatement($this->pdo->execute($statement));
		}
		catch (PDOException $e) {
			throw new InvalidSQLException("Invalid SQL. Statement could not be executed.");
		}
	}
	
	public function lastInsertId($name=NULL) {
		return $this->pdo->lastInsertId($name);
	}
	
	public function prepare($statement) {
		try {
			return new PDOStatement($this->pdo->prepare($statement));
		}
		catch (PDOException $e) {
			throw new InvalidSQLException("Invalid SQL. Statement could not be prepared.");
		}
	}
	
	public function query($statement) {
		try {
			return new PDOStatement($this->pdo->query($statement));
		}
		catch (PDOException $e) {
			throw new InvalidSQLException("Invalid SQL. Query could not be executed.");
		}
	}
	
	public function quote($string) {
		return $this->pdo->quote($string);
	}
	
	public function rollBack() {
		return $this->rollBack();
	}
}

?>