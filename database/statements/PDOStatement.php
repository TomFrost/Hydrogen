<?php
namespace hydrogen\database\statements;

use \PDO;
use \hydrogen\database\DatabaseStatement;

class PDOStatement extends DatabaseStatement {
	protected $stmt;
	
	public function __construct($pdoStatement) {
		$this->stmt = $pdoStatement;
	}
	
	public function bindColumn($column, &$param, $type=null) {
		if ($type === null) {
			$type = PDO::PARAM_STR;
			if (is_int($param))
				$type = PDO::PARAM_INT;
		}
		return $this->stmt->bindColumn($column, $param, $type);
	}
	
	public function bindParam($param, &$variable, $type=null) {
		if ($type === null) {
			$type = PDO::PARAM_STR;
			if (is_int($variable))
				$type = PDO::PARAM_INT;
		}
		return $this->stmt->bindParam($param, $variable, $type);
	}
	
	public function bindValue($param, $value, $type=null) {
		if ($type === null) {
			$type = PDO::PARAM_STR;
			if (is_int($value))
				$type = PDO::PARAM_INT;
		}
		return $this->stmt->bindValue($param, $value, $type);
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
	
	public function fetchInto($object) {
		$this->stmt->setFetchMode(PDO::FETCH_INTO, $object);
		return $this->fetch();
	}
	
	public function fetchIntoNew($classname, $ctorargs=array()) {
		$this->stmt->setFetchMode(PDO::FETCH_CLASS, $classname, $ctorargs);
		return $this->fetch();
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