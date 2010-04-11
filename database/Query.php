<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database;

use hydrogen\config\Config;
use hydrogen\database\exceptions\NoSuchVerbException;
use hydrogen\database\exceptions\InvalidSQLException;
use hydrogen\database\exceptions\DuplicateAliasException;
use hydrogen\database\DatabaseEngineFactory;
use hydrogen\database\statements\QueryStatement;

class Query {
	public static $legalVerbs = array('SELECT', 'INSERT', 'UPDATE', 'DELETE');
	public static $legalTypes = array('int', 'double', 'string', 'blob');
	public static $methods = array(
		'SELECT' => array(
			'distinct',
			'field',
			'from',
			'join',
			'where',
			'groupby',
			'having',
			'orderby',
			'limit'
			),
		'INSERT' => array(
			'ignore',
			'intoTable',
			'intoField',
			'values',
			'select'
			),
		'UPDATE' => array(
			'ignore',
			'table',
			'join',
			'set',
			'where',
			'orderby',
			'limit'
			),
		'DELETE' => array(
			'ignore',
			'table',
			'from',
			'join',
			'where',
			'orderby',
			'limit'
			)
		);
	protected $joinStack, $whereStack, $havingStack, $tableAliases, $reqJoinCond;
	protected $query, $verb, $autoTablePrefix, $dbengine;
	
	public function __construct($verb, $dbengine=false, $autoTablePrefix=true) {
		$this->verb = strtoupper($verb);
		if (!in_array($this->verb, static::$legalVerbs))
			throw new NoSuchVerbException("The verb '$verb' is invalid or not available.");
		$this->autoTablePrefix = $autoTablePrefix;
		$this->query = array();
		$this->query[$this->verb] = array();
		$this->joinStack = array();
		$this->whereStack = array();
		$this->havingStack = array();
		$this->tableAliases = array();
		$this->reqJoinCond = false;
		if(is_string($dbengine))
			$this->dbengine = DatabaseEngineFactory::getEngine($dbengine);
		else
			$this->dbengine = $dbengine ?: DatabaseEngineFactory::getEngine();
		$this->prefix = $this->dbengine->getTablePrefix();
	}
	
	public function getQueryTree() {
		return $this->query;
	}
	
	public function distinct() {
		$this->assertLegal();
		$this->setModifier('DISTINCT');
	}
	
	public function field($field, $alias=false, $vars=false) {
		$this->assertLegal();
		if (is_string($field) && ($field = trim($field)) !== '' && 
				($alias === false || (is_string($alias) && ($alias = trim($alias)) !== ''))) {
			$fieldData = array(
				'field' => $field,
				'alias' => $alias
				);
			if ($vars) {
				if (!is_array($vars))
					$vars = array($vars);
				$fieldData['vars'] = $vars;
			}
			if (!isset($this->query[$this->verb]['fields']))
				$this->query[$this->verb]['fields'] = array($fieldData);
			else if (!in_array($fieldData, $this->query[$this->verb]['fields']))
				$this->query[$this->verb]['fields'][] = $fieldData;
		}
		else
			throw new InvalidSQLException('Invalid field name or alias.');
	}
	
	public function from($table, $alias=false) {
		$this->assertLegal();
		if (is_string($table) && ($table = trim($table)) !== '' && 
				($alias === false || (is_string($alias) && ($alias = trim($alias)) !== ''))) {
			if ($this->autoTablePrefix && $this->prefix)
				$table = $this->prefix . $table;
			$from = array(
				'table' => $table,
				'alias' => $alias
				);
			if (!isset($this->query['FROM']))
				$this->query['FROM'] = array();
			if (!in_array($from, $this->query['FROM'])) {
				if ($alias && isset($this->tableAliases[$alias]))
					throw new DuplicateAliasException("Alias '$alias' already in use.");
				$this->query['FROM'][] = $from;
				$this->tableAliases[$alias] = $table;
			}
		}
		else
			throw new InvalidSQLException('Invalid table name or alias.');
	}
	
	public function groupby($field) {
		$this->assertLegal();
		if (!$field || ($field = trim($field)) === '')
			throw new InvalidSQLException('Invalid field name.');
		if ($this->query['GROUPBY'] != $field)
			$this->query['GROUPBY'] = $field;
	}
	
	public function having($expression, $vars=false, $logic=false) {
		$this->assertLegal();
		if (!isset($this->query['HAVING']))
			$this->query['HAVING'] = array();
		$this->addExpression($this->query['HAVING'], $this->havingStack, $expression, $vars, $logic);
	}
	
	public function havingCloseGroup() {
		$this->assertLegal('having');
		if (!isset($this->query['HAVING']))
			throw new InvalidSQLException('Group close failed: No groups have been opened.');
		$this->closeGroup($this->query['HAVING'], $this->havingStack);
	}

	public function havingOpenGroup($logic=false) {
		$this->assertLegal('having');
		if (!isset($this->query['HAVING']))
			$this->query['HAVING'] = array();
		$this->openGroup($this->query['HAVING'], $this->havingStack, $logic);
	}
	
	public function ignore() {
		$this->assertLegal();
		$this->setModifier('IGNORE');
	}
	
	public function intoField($field) {
		$this->assertLegal();
		if (!is_string($field) || ($field = trim($field)) === '')
			throw new InvalidSQLException('Invalid field name.');
		if (!isset($this->query['INTO']))
			$this->query['INTO'] = array();
		if (!isset($this->query['INTO']['fields']))
			$this->query['INTO']['fields'] = array();
		$this->query['INTO']['fields'][] = $field;
	}
	
	public function intoTable($table) {
		$this->assertLegal();
		if (!is_string($table) || ($table = trim($table)) === '')
			throw new InvalidSQLException('Invalid table name.');
		if ($this->autoTablePrefix && $this->prefix)
			$table = $this->prefix . $table;
		if (!isset($this->query['INTO']))
			$this->query['INTO'] = array();
		$this->query['INTO']['table'] = $table;
	}
	
	public function join($table, $alias=false, $type=false) {
		$this->assertLegal();
		if ($this->reqJoinCond)
			throw new InvalidSQLException('Cannot create new JOIN: A condition is required ' .
				'for the currently active JOIN.');
		if (is_string($table) && ($table = trim($table)) !== '' && 
				($alias === false || (is_string($alias) && ($alias = trim($alias)) !== ''))) {
			if ($alias && isset($this->tableAliases[$alias]))
				throw new DuplicateAliasException("Alias '$alias' already in use.");
			if ($this->autoTablePrefix && $this->prefix)
				$table = $this->prefix . $table;
			$this->tableAliases[$alias] = $table;
			if (!isset($this->query['JOIN']))
				$this->query['JOIN'] = array();
			$this->query['JOIN'][] = array(
				'type' => $type,
				'table' => $table,
				'alias' => $alias
				);
			$this->reqJoinCond = true;
			$this->joinStack = array();
		}
		else
			throw new InvalidSQLException('Invalid table name or alias.');
	}
	
	public function limit($rowcount, $offset=false) {
		$this->assertLegal();
		$this->query['LIMIT'] = array(
			'rowcount' => $rowcount,
			'offset' => $offset
			);
	}
	
	public function on($expression, $vars=false, $logic=false) {
		$this->assertLegal('join');
		if (!isset($this->query['JOIN']))
			throw new InvalidSQLException('JOIN must be used before any ON arguments are specified.');
		$curJoin = &$this->query['JOIN'][count($this->query['JOIN']) - 1];
		if (isset($curJoin['condition']) && $curJoin['condition'] !== 'ON')
			throw new InvalidSQLException('Cannot use ON after a different JOIN condition.');
		if (!isset($curJoin['condition']))
			$curJoin['condition'] = 'ON';
		if (!isset($curJoin['args']))
			$curJoin['args'] = array();
		$this->addExpression($curJoin['args'], $this->joinStack, $expression, $vars, $logic);
		$this->reqJoinCond = false;
	}
	
	public function onCloseGroup() {
		$this->assertLegal('join');
		if (!isset($this->query['JOIN']))
			throw new InvalidSQLException('JOIN must be used before any ON arguments are specified.');
		$curJoin = &$this->query['JOIN'][count($this->query['JOIN']) - 1];
		if (!isset($curJoin['condition']) || $curJoin['condition'] !== 'ON')
			throw new InvalidSQLException('Cannot use ON after a different JOIN condition.');
		$this->closeGroup($curJoin['args'], $this->joinStack);
	}
	
	public function onOpenGroup($logic=false) {
		$this->assertLegal('join');
		if (!isset($this->query['JOIN']))
			throw new InvalidSQLException('JOIN must be used before any ON arguments are specified.');
		$curJoin = &$this->query['JOIN'][count($this->query['JOIN']) - 1];
		if (isset($curJoin['condition']) && $curJoin['condition'] !== 'ON')
			throw new InvalidSQLException('Cannot use ON after a different JOIN condition.');
		if (!isset($curJoin['condition']))
			$curJoin['condition'] = 'ON';
		$this->openGroup($curJoin['args'], $this->joinStack, $logic);
		$this->reqJoinCond = true;
	}
	
	public function orderby($field, $direction='ASC') {
		$this->assertLegal();
		if (!$field || ($field = trim($field)) === '')
			throw new InvalidSQLException('Invalid field name.');
		$direction = strtoupper($direction);
		if ($direction != 'ASC' && $direction != 'DESC')
			throw new InvalidSQLException('Order direction not supported: ' . $direction);
		if (!isset($this->query['ORDERBY']))
			$this->query['ORDERBY'] = array();
		$this->query['ORDERBY'][] = array(
			'field' => $field,
			'direction' => $direction
			);
	}
	
	public function prepare() {
		$eclass = get_class($this->dbengine);
		$fclass = $eclass::QUERY_FORMATTER;
		$formatter = new $fclass($this->query);
		$stmt = $this->dbengine->prepare($formatter->getPreparedQuery());
		return new QueryStatement($stmt, $formatter);
	}
	
	public function select($queryObj) {
		$this->assertLegal();
		if (isset($this->query['VALUES']))
			throw new InvalidSQLException('SELECT cannot be used in conjunction with VALUES.');
		$this->query['SELECT'] = $queryObj->getQueryTree();
	}
	
	public function set($expression, $vars=false) {
		$this->assertLegal();
		if (!$expression || ($expression = trim($expression)) === '')
			throw new InvalidSQLException('Invalid expression.');
		if (!isset($this->query['SET']))
			$this->query['SET'] = array();
		if ($vars === false)
			$vars = array();
		else if (!is_array($vars))
			$vars = array($vars);
		$this->query['SET'][] = array(
			'expr' => $expression,
			'vars' => $vars
			);
	}
	
	public function table($table, $alias=false) {
		$this->assertLegal();
		if (is_string($table) && ($table = trim($table)) !== '' && 
				($alias === false || (is_string($alias) && ($alias = trim($alias)) !== ''))) {
			if ($this->autoTablePrefix && $this->prefix)
				$table = $this->prefix . $table;
			$table = array(
				'table' => $table,
				'alias' => $alias
				);
			if (!isset($this->query[$this->verb]['tables']))
				$this->query[$this->verb]['tables'] = array();
			if (!in_array($table, $this->query[$this->verb]['tables'])) {
				if ($alias && isset($this->tableAliases[$alias]))
					throw new DuplicateAliasException("Alias '$alias' already in use.");
				$this->query[$this->verb]['tables'][] = $table;
				$this->tableAliases[$alias] = $table;
			}
		}
		else
			throw new InvalidSQLException('Invalid table name or alias.');
	}
	
	public function using($fields) {
		$this->assertLegal('join');
		if (!isset($this->query['JOIN']))
			throw new InvalidSQLException('JOIN must be used before any USING arguments are specified.');
		$curJoin = &$this->query['JOIN'][count($this->query['JOIN']) - 1];
		if (isset($curJoin['condition']) && $curJoin['condition'] !== 'USING')
			throw new InvalidSQLException('Cannot use USING after a different JOIN condition.');
		if (!isset($curJoin['condition']))
			$curJoin['condition'] = 'USING';
		if (!isset($curJoin['args']))
			$curJoin['args'] = array();
		if (!is_array($fields))
			$fields = array($fields);
		foreach ($fields as $field) {
			if (!in_array($field, $curJoin['args']))
				$curJoin['args'][] = $field;
		}
	}
	
	public function values($expression, $vars=false) {
		$this->assertLegal();
		if (isset($this->query['SELECT']))
			throw new InvalidSQLException('VALUES cannot be used in conjuction with SELECT.');
		if (!isset($this->query['VALUES']))
			$this->query['VALUES'] = array();
		if ($vars === false)
			$vars = array();
		else if (!is_array($vars))
			$vars = array($vars);
		$valblock = array(
			'expr' => $expression,
			'vars' => $vars
			);
		$this->query['VALUES'][] = $valblock;
	}
	
	public function where($expression, $vars=false, $logic=false) {
		$this->assertLegal();
		if (!isset($this->query['WHERE']))
			$this->query['WHERE'] = array();
		$this->addExpression($this->query['WHERE'], $this->whereStack, $expression, $vars, $logic);
	}
	
	public function whereCloseGroup() {
		$this->assertLegal('where');
		if (!isset($this->query['WHERE']))
			throw new InvalidSQLException('Group close failed: No groups have been opened.');
		$this->closeGroup($this->query['WHERE'], $this->whereStack);
	}
	
	public function whereOpenGroup($logic=false) {
		$this->assertLegal('where');
		if (!isset($this->query['WHERE']))
			$this->query['WHERE'] = array();
		$this->openGroup($this->query['WHERE'], $this->whereStack, $logic);
	}
	
	protected function openGroup(&$tree, &$stack, $logic, $defaultLogic='AND') {
		$branch = &$tree;
		foreach($stack as $node)
			$branch = &$branch[$node]['unit'];
		if (($count = count($branch)) == 0)
			$logic = false;
		else if (!$logic)
			$logic = $defaultLogic;
		$branch[] = array(
			'logic' => $logic,
			'unittype' => 'group',
			'unit' => array()
			);
		$stack[] = $count;
	}
	
	protected function closeGroup(&$tree, &$stack) {
		$branch = &$tree;
		if (count($stack) == 0)
			throw new InvalidSQLException('Group close failed: No groups have been opened.');
		foreach($stack as $node)
			$branch = &$branch[$node]['unit'];
		if (count($branch) == 0)
			throw new InvalidSQLException('Groups cannot be closed when empty.');
		unset($stack[count($stack) - 1]);
	}
	
	protected function addExpression(&$tree, &$stack, $expression, $vars=false, $logic=false, $defaultLogic='AND') {
		$branch = &$tree;
		foreach($stack as $node)
			$branch = &$branch[$node]['unit'];
		if (count($branch) == 0)
			$logic = false;
		else if (!$logic)
			$logic = $defaultLogic;
		if ($vars === false)
			$vars = array();
		else if (!is_array($vars))
			$vars = array($vars);
		$branch[] = array(
			'logic' => $logic,
			'unittype' => 'expr',
			'unit' => array(
				'expr' => $expression,
				'vars' => $vars
				)
			);
	}
	
	protected function setModifier($modifier) {
		if (!isset($this->query[$this->verb]['modifiers']))
			$this->query[$this->verb]['modifiers'] = array();
		if (!in_array($modifier, $this->query[$this->verb]['modifiers']))
			$this->query[$this->verb]['modifiers'][] = $modifier;
	}
	
	protected function assertLegal($method=false) {
		if (!$method) {
			$back = debug_backtrace();
			$method = $back[1]['function'];
		}
		if (!in_array($method, static::$methods[$this->verb])) {
			$class = get_class($this);
			throw new InvalidSQLException("${class}->${method}() cannot be used on " . $this->verb . " queries.");
		}
	}
}

?>