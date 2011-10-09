<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database\formatters;

use hydrogen\database\QueryFormatter;
use hydrogen\database\exceptions\InvalidSQLException;

class StandardSQLFormatter extends QueryFormatter {
	protected static $orderOfOperations = array(
		'SELECT' => array(
			'SELECT',
			'FROM',
			'JOIN',
			'WHERE',
			'GROUPBY',
			'HAVING',
			'ORDERBY',
			'LIMIT'
			),
		'INSERT' => array(
			'INSERT',
			'INTO',
			'VALUES',
			'SELECT'
			),
		'UPDATE' => array(
			'UPDATE',
			'JOIN',
			'SET',
			'WHERE',
			'ORDERBY',
			'LIMIT'
			),
		'DELETE' => array(
			'DELETE',
			'FROM',
			'JOIN',
			'WHERE',
			'ORDERBY',
			'LIMIT'
			)
		);
	protected $incVals = array(), $verb, $parsed;
	
	public function __construct($queryArray) {
		reset($queryArray);
		$this->verb = key($queryArray);
		$this->parsed = $this->parseQueryArray($queryArray);
	}
	
	public function getPreparedQuery() {
		$str = '';
		$nodes = count($this->parsed);
		for ($i = 0; $i < $nodes; $i += 2)
			$str .= $this->parsed[$i] . '?';
		return substr($str, 0, -1);
	}
	
	public function getPreparedValues($valueArray=false) {
		$vars = array();
		$inc = 0;
		$nodes = count($this->parsed);
		for ($i = 1; $i < $nodes; $i += 2) {
			if ($this->parsed[$i] == '?') {
				if (!isset($this->incVals[$inc]))
					throw new InvalidSQLException('Value mismatch error: More ? symbols were used than values provided.');
				$vars[] = &$this->incVals[$inc];
				$inc++;
			}
			else {
				if (!$valueArray || !isset($valueArray[$this->parsed[$i]]))
					throw new InvalidSQLException('Named dynamic value "' . $this->parsed[$i] . '" was not defined.');
				$vars[] = &$valueArray[$this->parsed[$i]];
			}
		}
		return $vars;
	}
	
	public function getCompleteQuery($valueArray=false) {
		$str = '';
		$vars = $this->getPreparedValues($valueArray);
		$nodes = count($this->parsed);
		for ($i = 0; $i < $nodes; $i += 2) {
			$val = false;
			if (isset($vars[$i / 2])) {
				if (is_string($vars[$i / 2]))
					$val = "'" . $this->escapeString($vars[$i / 2]) . "'";
				else
					$val = $vars[$i / 2];
			}
			$str .= $this->parsed[$i] . ($val ?: '');
		}
		return $str;
	}
	
	protected function parseQueryArray(&$queryArray) {
		$ops = static::$orderOfOperations[$this->verb];
		$queryStr = '';
		foreach ($ops as $op) {
			if (isset($queryArray[$op])) {
				$clause = call_user_func_array(array($this, "parse$op"), array(&$queryArray[$op]));
				$queryStr .=  $clause . ' ';
			}
		}
		$queryStr = substr($queryStr, 0, -1);
		$splitRegex = '/' .
			// Match the variable pattern
			'(?<=[\s,\(]):?((?<=:)\w++|(?<!:)\?)' .
			// Only if it is followed by an even number of unescaped quotes
			'(?=(?:(?:(?:[^\\\'\\\\]++|\\\\.)*+\\\'){2})*+(?:[^\\\'\\\\]++|\\\\.)*+$)' .
			'/';
		return preg_split($splitRegex, $queryStr, -1, PREG_SPLIT_DELIM_CAPTURE);
	}
	
	protected function parseDELETE(&$clause) {
		$str = 'DELETE';
		if (isset($clause['modifiers']) && $clause['modifiers']) {
			foreach ($clause['modifiers'] as $mod)
				$str .= ' ' . $mod;
		}
		if (isset($clause['tables'])) {
			$str .= ' ';
			foreach ($clause['tables'] as $table) {
				if (!isset($table['table']))
					throw new InvalidSQLException('No table specified in UPDATE table grouping.');
				$str .= $table['table'];
				if (isset($table['alias']) && $table['alias'])
					$str .= ' ' . $table['alias'];
				$str .= ', ';
			}
			$str = substr($str, 0, -2);
		}
		return $str;
	}
	
	protected function parseFROM(&$clause) {
		$str = 'FROM ';
		foreach ($clause as $table) {
			if (!isset($table['table']))
				throw new InvalidSQLExcpetion('FROM argument has no table.');
			$str .= $table['table'];
			if (isset($table['alias']) && $table['alias'])
				$str .= ' ' . $table['alias'];
			$str .= ', ';
		}
		return substr($str, 0, -2);
	}
	
	protected function parseGROUPBY(&$clause) {
		return 'GROUP BY ' . $clause;
	}
	
	protected function parseHAVING(&$clause) {
		return 'HAVING ' . $this->parseExpressionTree($clause);
	}
	
	protected function parseINSERT(&$clause) {
		$str = 'INSERT';
		if (isset($clause['modifiers']) && $clause['modifiers']) {
			foreach ($clause['modifiers'] as $mod)
				$str .= ' ' . $mod;
		}
		return $str;
	}
	
	protected function parseINTO(&$clause) {
		if (!isset($clause['table']))
			throw new InvalidSQLException('No table specified for the INTO clause.');
		$str = 'INTO ' . $clause['table'];
		if (isset($clause['fields']) && $clause['fields']) {
			$str .= ' (';
			foreach ($clause['fields'] as $field)
				$str .= $field . ', ';
			$str = substr($str, 0, -2) . ')';
		}
		return $str;
	}
	
	protected function parseJOIN(&$clause) {
		$str = '';
		foreach ($clause as $join) {
			if (!isset($join['table']))
				throw new InvalidSQLExcpetion('No table specified in JOIN clause.');
			if (!isset($join['condition']) || ($join['condition'] != 'ON' && $join['condition'] != 'USING'))
				throw new InvalidSQLException('Join condition must be set to either ON or USING.');
			if (!isset($join['args']))
				throw new InvalidSQLException('Join condition\'s args are not set.');
			if (isset($join['type']) && $join['type'])
				$str .= $join['type'] . ' ';
			$str .= 'JOIN ' . $join['table'] . ' ';
			if (isset($join['alias']) && $join['alias'])
				$str .= $join['alias'] . ' ';
			$str .= $join['condition'] . ' ';
			if ($join['condition'] == 'ON')
				$str .= $this->parseExpressionTree($join['args']) . ' ';
			else if ($join['condition'] == 'USING') {
				$str .= '(';
				foreach ($join['args'] as $arg)
					$str .= $arg . ', ';
				$str = substr($str, 0, -2) . ') ';
			}
		}
		return substr($str, 0, -1);
	}
	
	protected function parseLIMIT(&$clause) {
		$str = 'LIMIT ';
		if (!isset($clause['rowcount']))
			throw new InvalidSQLException('Row count not specified in LIMIT clause.');
		if (isset($clause['offset']) && $clause['offset'])
			$str .= $clause['offset'] . ', ';
		$str .= $clause['rowcount'];
		return $str;
	}
	
	protected function parseORDERBY(&$clause) {
		$str = 'ORDER BY ';
		if (!is_array($clause) || count($clause) == 0)
			throw new InvalidSQLException('No fields provided in ORDER BY clause.');
		foreach ($clause as $field) {
			if (!isset($field['field']))
				throw new InvalidSQLException('Field not specified in ORDER BY argument.');
			$str .= $field['field'];
			if (isset($field['direction']))
				$str .= ' ' . $field['direction'];
			$str .= ', ';
		}
		return substr($str, 0, -2);
	}
	
	protected function parseSELECT(&$clause) {
		if (!isset($clause['SELECT'])) {
			$str = 'SELECT ';
			if (isset($clause['modifiers']) && $clause['modifiers']) {
				foreach ($clause['modifiers'] as $mod)
					$str .= $mod . ' ';
			}
			if (!isset($clause['fields']))
				throw new InvalidSQLException('SELECT has no fields.');
			foreach ($clause['fields'] as $field) {
				if (!isset($field['field']))
					throw new InvalidSQLException('Field name not set in SELECT statement.');
				$str .= $field['field'];
				if (isset($field['alias']) && $field['alias'])
					$str .= ' ' . $field['alias'];
				$str .= ', ';
				if (isset($field['vars'])) {
					foreach ($field['vars'] as $var)
						$this->incVals[] = $var;
				}
			}
			return substr($str, 0, -2);
		}
		else {
			$class = get_class($this);
			$sub = new $class($clause);
			$vals = $sub->getPreparedValues();
			foreach ($vals as $val)
				$this->incVals[] = $val;
			return $sub->getPreparedQuery();
		}
	}
	
	protected function parseSET(&$clause) {
		$str = 'SET ';
		if (!is_array($clause) || count($clause) == 0)
			throw new InvalidSQLException('No expressions provided in SET clause.');
		foreach ($clause as $set) {
			if (!isset($set['expr']))
				throw new InvalidSQLException('No expression set in SET argument.');
			$str .= $set['expr'] . ', ';
			if (isset($set['vars'])) {
				foreach ($set['vars'] as $var)
					$this->incVals[] = $var;
			}
		}
		return substr($str, 0, -2);
	}
	
	protected function parseVALUES(&$clause) {
		$str = 'VALUES ';
		if (!is_array($clause) || count($clause) == 0)
			throw new InvalidSQLException('No values provided in VALUES clause.');
		foreach ($clause as $set) {
			if (!isset($set['expr']))
				throw new InvalidSQLException('No expression set for VALUES clause.');
			$str .= $set['expr'] . ', ';
			if (isset($set['vars'])) {
				foreach ($set['vars'] as $var)
					$this->incVals[] = $var;
			}
		}
		return substr($str, 0, -2);
	}
	
	protected function parseUPDATE(&$clause) {
		$str = 'UPDATE ';
		if (isset($clause['modifiers']) && $clause['modifiers']) {
			foreach ($clause['modifiers'] as $mod)
				$str .= $mod . ' ';
		}
		if (!is_array($clause['tables']) || count($clause['tables']) == 0)
			throw new InvalidSQLException('No tables provided in UPDATE clause.');
		foreach ($clause['tables'] as $table) {
			if (!isset($table['table']))
				throw new InvalidSQLException('No table specified in UPDATE table grouping.');
			$str .= $table['table'];
			if (isset($table['alias']) && $table['alias'])
				$str .= ' ' . $table['alias'];
			$str .= ', ';
		}
		return substr($str, 0, -2);
	}
	
	protected function parseWHERE(&$clause) {
		return 'WHERE ' . $this->parseExpressionTree($clause);
	}
	
	protected function parseExpressionTree(&$tree) {
		$str = '';
		$first = true;
		foreach ($tree as $node) {
			if (!$first) {
				if (!isset($node['logic']))
					throw new InvalidSQLException('Logic (AND/OR) not defined for expression tree node.');
				$str .= $node['logic'] . ' ';
			}
			else
				$first = false;
			if (!isset($node['unittype']))
				throw new InvalidSQLException('Unittype not defined for expression tree node.');
			if (!isset($node['unit']))
				throw new InvalidSQLException('Unit not defined for expression tree node.');
			if ($node['unittype'] == 'expr') {
				if (!isset($node['unit']['expr']))
					throw new InvalidSQLException('Expression not defined in expression tree node unit.');
				$str .= $node['unit']['expr'] . ' ';
				if (isset($node['unit']['vars'])) {
					foreach ($node['unit']['vars'] as $var)
						$this->incVals[] = $var;
				}
			}
			else if ($node['unittype'] == 'group')
				$str .= '(' . $this->parseExpressionTree($node['unit']) . ') ';
		}
		return substr($str, 0, -1);
	}
}

?>
