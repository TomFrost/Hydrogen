<?php
namespace hydrogen\database\statements;

use \PDO;
use \hydrogen\database\DatabaseStatement;

class PDOStatement extends DatabaseStatement {
	protected $stmt;
	
	public function __construct($pdoStatement) {
		$this->stmt = $pdoStatement;
	}
	
	public function bindColumn($column, &$param) {
		return $this->stmt->bindColumn($column, $param);
	}
	
	public function bindParam($param, &$variable) {
		return $this->stmt->bindParam($param, $variable);
	}
	
	public function bindValue($param, $value) {
		return $this->stmt->bindValue($param, $value);
	}
	
	public function closeCursor() {
		return $this->stmt->closeCursor();
	}
	
	public function columnCount() {
		return $this->stmt->columnCount();
	}
	
	public function errorCode() {
		return $this->stmt->errorCode();
	}
	
	public function errorInfo() {
		return $this->stmt->errorInfo();
	}
	
	public function execute($inputParameters=array()) {
		return $this->stmt->execute($inputParameters);
	}
	
	public function fetchAll() {
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function fetchAssoc() {
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function fetchBound() {
		return $this->stmt->fetch(PDO::FETCH_BOUND);
	}
	
	public function fetchObject() {
		return $this->stmt->fetch(PDO::FETCH_OBJ);
	}
	
	public function nextRowset() {
		return $this->stmt->nextRowset();
	}
	
	public function rowCount() {
		return $this->stmt->rowCount();
	}
}

?>