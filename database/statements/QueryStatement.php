<?php
namespace hydrogen\database\statements;

use \hydrogen\database\DatabaseStatement;

class QueryStatement extends DatabaseStatement {
	protected $stmt, $formatter, $vars;
	
	public function __construct($stmt, $formatter) {
		$this->stmt = $stmt;
		$this->formatter = $formatter;
		$this->vars = array();
	}
	
	public function bindColumn($column, &$param) {
		return $this->stmt->bindColumn($column, $param);
	}

	public function bindParam($param, &$variable) {
		while ($param[0] == ':')
			$param = substr($param, 1);
		$this->vars[$param] = &$variable;
		return true;
	}

	public function bindValue($param, $value) {
		return $this->bindParam($param, $value);
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
		$vars = array_merge($inputParameters, $this->vars);
		$vals = $this->formatter->getPreparedValues($vars);
		return $this->stmt->execute($vals);
	}

	public function fetchAll() {
		return $this->stmt->fetchAll();
	}

	public function fetchAssoc() {
		return $this->stmt->fetchAssoc();
	}

	public function fetchBound() {
		return $this->stmt->fetchBound();
	}

	public function fetchObject() {
		return $this->stmt->fetchObject();
	}
	
	public function getQuery() {
		return $this->formatter->getCompleteQuery();
	}

	public function nextRowset() {
		return $this->stmt->nextRowset();
	}

	public function rowCount() {
		return $this->stmt->rowCount();
	}

}

?>