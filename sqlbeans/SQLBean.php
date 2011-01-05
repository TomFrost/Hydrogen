<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\sqlbeans;

use hydrogen\database\Query;
use hydrogen\database\DatabaseEngine;
use hydrogen\database\DatabaseEngineFactory;
use hydrogen\sqlbeans\exceptions\MissingPrimaryKeyException;
use hydrogen\sqlbeans\exceptions\NoStoredValuesException;
use hydrogen\sqlbeans\exceptions\BeanMapException;
use hydrogen\sqlbeans\exceptions\QueryFailedException;
use hydrogen\sqlbeans\BeanRegistry;

abstract class SQLBean {
	protected static $fields, $tableNoPrefix, $primaryKey, $primaryKeyIsAutoIncrement;
	protected static $tableAlias, $beanMap;
	protected $stored, $mapped, $changed, $sqlkeys, $dbengine, $dbreconstruct;
	
	public function __construct($dbengine=false, &$dbrow=false, $fieldPrefix=false, $bindRow=true) {
		$class = get_class($this);
		$this->stored = array();
		$this->mapped = array();
		$this->changed = array();
		$this->sqlkeys = array();
		if ($dbrow) {
			if ($bindRow)
				$row = &$dbrow;
			else
				$row = $dbrow;
			foreach ($row as $key => $value) {
				$ingest = true;
				if ($fieldPrefix) {
					if (strpos($key, $fieldPrefix) === 0)
						$key = substr($key, strlen($fieldPrefix));
					else
						$ingest = false;
				}
				if ($ingest && in_array($key, static::$fields))
					$this->stored[$key] = $value;
			}
		}
		$this->dbengine = ($dbengine instanceof DatabaseEngine) ?
			$dbengine : DatabaseEngineFactory::getEngine($dbengine);
		$this->dbreconstruct = $this->dbengine->getReconstructArray();
	}
	
	public function __sleep() {
		return array('stored', 'mapped', 'changed', 'sqlkeys', 'dbreconstruct');
	}
	
	public function __wakeup() {
		$this->dbengine = DatabaseEngineFactory::getCustomEngine(
			$this->dbreconstruct['engine'],
			$this->dbreconstruct['host'],
			$this->dbreconstruct['port'],
			$this->dbreconstruct['socket'],
			$this->dbreconstruct['database'],
			$this->dbreconstruct['username'],
			$this->dbreconstruct['password'],
			$this->dbreconstruct['table_prefix']
			);
	}
	
	public function __get($var) {
		return $this->get($var);
	}
	
	public function __isset($var) {
		return isset($this->stored[$var]) ||
			method_exists($this, 'get_' . $var);
	}
	
	public function __set($var, $val) {
		return $this->set($var, $val);
	}
	
	public function get($var) {
		$method = 'get_' . $var;
		if (method_exists($this, $method))
			return $this->$method();
		return isset($this->stored[$var]) ? $this->stored[$var] : false;
	}
	
	public function set($var, $val, $isSQLFunction=false) {
		$success = true;
		if ($isSQLFunction)
			$this->sqlkeys[$var] = true;
		$method = 'set_' . $var;
		if (method_exists($this, $method))
			$success = $this->$method($val, $isSQLFunction);
		else
			$this->stored[$var] = $val;
		if ($success && !$isSQLFunction)
			$this->sqlkeys[$var] = false;
		else if ($success && $isSQLFunction)
			$this->sqlkeys[$var] = true;
		if ($success)
			$this->changed[$var] = true;
		return $success;
	}
	
	public function fieldChanged($field) {
		return isset($this->stored[$field]) && isset($this->changed[$field]) && $this->changed[$field];
	}
	
	public function hasMappedBeans() {
		return count($this->mapped) > 0;
	}
	
	public function getMapped($name) {
		return isset($this->mapped[$name]) ? $this->mapped[$name] : false;
	}
	
	public function setMapped($name, $bean) {
		$this->mapped[$name] = $bean;
	}
	
	public static function getFields() {
		return static::$fields;
	}
	
	public static function getPrimaryKey() {
		return static::$primaryKey;
	}
	
	protected static function getMappedBean($dbengine, $mapPath, $mapOverride, &$resultRow, $registry=false, $beanClass=false, 
			$parentAlias=false, $tableAliasOverride=false, $usedBeans=false) {
		$bean = $beanClass ?: get_called_class();
		$alias = ($parentAlias ? $parentAlias . '_' : '') . ($tableAliasOverride ?: $bean::$tableAlias);
		if ($registry) {
			$keyfield = $alias . '_' . $bean::$primaryKey;
			if (isset($resultRow[$keyfield])) {
				$cachedBean = $registry->getBean($bean, $resultRow[$keyfield]);
				if ($cachedBean)
					return $cachedBean;
			}
		}
		if (!is_array($usedBeans))
			$usedBeans = array();
		$usedBeans[] = $bean;
		$beanInst = new $bean($dbengine, $resultRow, $alias . '_');
		$beanMap = $mapOverride ?: (isset($bean::$beanMap) ? $bean::$beanMap : false);
		if (is_array($beanMap) && count($beanMap) > 0) {
			foreach ($beanMap as $mapname => $mapping) {
				if (!in_array($mapping['joinBean'], $usedBeans)) {
					if ($mapPath === true || (is_array($mapPath) && in_array($mapname, array_keys($mapPath)))) {
						if (is_array($mapPath))
							$sendPath = $mapPath[$mapname];
						else
							$sendPath = $mapPath;
						$override = isset($mapping['tableAliasOverride']) ? $mapping['tableAliasOverride'] : false;
						$mapped = static::getMappedBean($dbengine, $sendPath, false, $resultRow, $registry, $mapping['joinBean'], 
							$alias, $override, $usedBeans);
						$beanInst->setMapped($mapname, $mapped);
					}
				}
			}
		}
		if (($primkey = $beanInst->get($bean::$primaryKey)) !== false)
			$registry->setBean($beanInst, $primkey);
		return $beanInst;
	}
	
	protected static function buildMappedSelect($query, $mapPath=false, $mapOverride=false, $beanClass=false,
			$joinCondType=false, $joinCond=false, $joinType=false, $parentAlias=false, $tableAliasOverride=false, 
			$usedBeans=false) {
		$bean = $beanClass ?: get_called_class();
		if (!isset($bean::$tableAlias))
			throw new BeanMapException('tableAlias undefined for bean ' . $bean);
		if (!is_array($usedBeans))
			$usedBeans = array();
		$usedBeans[] = $bean;
		$alias = ($parentAlias ? $parentAlias . '_' : '') . ($tableAliasOverride ?: $bean::$tableAlias);
		foreach ($bean::$fields as $field)
			$query->field($alias . '.' . $field, $alias . '_' . $field);
		if (!$joinCondType)
			$query->from($bean::$tableNoPrefix, $alias);
		else {
			$query->join($bean::$tableNoPrefix, $alias, $joinType);
			if ($joinCondType == 'foreignKey')
				$query->on("$parentAlias.$joinCond = $alias." . $bean::$primaryKey);
			else if ($joinCondType == 'on') {
				foreach ($joinCond as $cond)
					$query->on("$parentAlias.$cond[0] $cond[1] $alias.$cond[2]");
			}
		}
		$beanMap = $mapOverride ?: (isset($bean::$beanMap) ? $bean::$beanMap : false);
		if (is_array($beanMap) && count($beanMap) > 0) {
			foreach ($beanMap as $mapname => $mapping) {
				if (!isset($mapping['joinBean']) || (!isset($mapping['foreignKey']) && !isset($mapping['on'])))
					throw new BeanMapException("All beanMap entries for $bean must define " .
						"'joinBean' and either 'foreignKey' or 'on'.");
				if (!in_array($mapping['joinBean'], $usedBeans)) {
					if ($mapPath === true || (is_array($mapPath) && in_array($mapname, array_keys($mapPath)))) {
						if (is_array($mapPath))
							$sendPath = $mapPath[$mapname];
						else
							$sendPath = $mapPath;
						$joinType = isset($mapping['joinType']) ? $mapping['joinType'] : false;
						$override = isset($mapping['tableAliasOverride']) ? $mapping['tableAliasOverride'] : false;
						if (isset($mapping['foreignKey'])) {
							$joinCondType = 'foreignKey';
							$joinCond = $mapping['foreignKey'];
						}
						else {
							$joinCondType = 'on';
							$joinCond = $mapping['on'];
						}
						static::buildMappedSelect($query, $sendPath, false, $mapping['joinBean'], $joinCondType, $joinCond, 
							$joinType, $alias, $override, $usedBeans);
					}
				}
			}
		}
	}
	
	public static function select($query=false, $doMapping=false, $mapOverride=false, $dbengine=false) {
		$dbengine = ($dbengine instanceof DatabaseEngine) ?
			$dbengine : DatabaseEngineFactory::getEngine($dbengine);
		if (!$query)
			$query = new Query('SELECT', $dbengine);
		if ($mapOverride)
			$mapOverride = array_merge(static::$beanMap, $mapOverride);
		if ($doMapping !== false)
			static::buildMappedSelect($query, $doMapping, $mapOverride);
		else {
			foreach (static::$fields as $field)
				$query->field($field);
			$query->from(static::$tableNoPrefix);
		}
		$stmt = $query->prepare();
		if (!$stmt->execute()) {
			$info = $stmt->errorInfo();
			$e = new QueryFailedException($info[2]);
			$e->errorInfo = $info;
			throw $e;
		}
		$results = array();
		$beanClass = get_called_class();
		$registry = new BeanRegistry();
		while ($row = $stmt->fetchAssoc()) {
			if ($doMapping !== false)
				$bean = static::getMappedBean($dbengine, $doMapping, $mapOverride, $row, $registry);
			else
				$bean = new $beanClass($dbengine, $row, false);
			$results[] = $bean;
		}
		return $results;
	}
	
	public function insert($ignore=false) {
		$query = new Query('INSERT', $this->dbengine);
		if ($ignore)
			$query->ignore();
		$query->intoTable(static::$tableNoPrefix);
		$vals = array();
		$template = '(';
		foreach ($this->stored as $key => $val) {
			$valid = true;
			if ($key == static::$primaryKey && static::$primaryKeyIsAutoIncrement)
				$valid = false;
			if ($valid) {
				$query->intoField($key);
				if ($this->sqlkeys[$key])
					$template .= $val . ', '; 
				else {
					$template .= '?, ';
					$vals[] = $val;
				}
			}
		}
		if ($template == '(')
			throw new NoStoredValuesException('INSERT cannot be completed: No values to be inserted.');
		$template = substr($template, 0, -2) . ')';
		$query->values($template, $vals);
		$stmt = $query->prepare();
		if (!$stmt->execute()) {
			$info = $stmt->errorInfo();
			$e = new QueryFailedException($info[2]);
			$e->errorInfo = $info;
			throw $e;
		}
		return true;
	}
	
	public function update($allFields=false, $remap=false) {
		if (!isset($this->stored[static::$primaryKey])) {
			$class = get_class($this);
			throw new MissingPrimaryKeyException($class . '->update() cannot be called until a primary key is set.');
		}
		$changedMap = array();
		$query = new Query('UPDATE', $this->dbengine);
		$query->table(static::$tableNoPrefix);
		$fieldSet = $allFields ? $this->stored : $this->changed;
		foreach ($fieldSet as $key => $changed) {
			if ($remap && $changed) {
				foreach (static::$beanMap as $name => $args) {
					if ($args['foreignKey'] == $key)
						$changedMap[] = $name;
				}
			}
			if (!$allFields && $changed) {
				if ($this->sqlkeys[$key])
					$query->set($key . ' = ' . $this->stored[$key]);
				else
					$query->set($key . ' = ?', $this->stored[$key]);
			}
		}
		$query->where(static::$primaryKey . ' = ?', $this->stored[static::$primaryKey]);
		$query->limit(1);
		$stmt = $query->prepare();
		if (!$stmt->execute()) {
			$info = $stmt->errorInfo();
			$e = new QueryFailedException($info[2]);
			$e->errorInfo = $info;
			throw $e;
		}
		$this->changed = array();
		if ($remap) {
			foreach ($changedMap as $map) {
				if ($this->mapped[$map]) {
					$class = static::$beanMap[$map]['joinBean'];
					$query = new Query('SELECT', $this->dbengine);
					$fkey = static::$beanMap[$map]['foreignKey'];
					$hasMapped = $this->mapped[$map]->hasMappedBeans();
					if ($hasMapped)
						$query->where($class::$tableAlias . ".$fkey = ?", $this->stored[$fkey]);
					else
						$query->where($fkey . ' = ?', $this->stored[$fkey]);
					$query->limit(1);
					$result = $class::select($query, $hasMapped, false, $this->dbengine);
					$this->mapped[$map] = $result ? $result[0] : false;
				}
			}
		}
		return true;
	}
	
	public function delete() {
		if (!isset($this->stored[static::$primaryKey])) {
			$class = get_class($this);
			throw new MissingPrimaryKeyException($class . '->delete() cannot be called until a primary key is set.');
		}
		$query = new Query('DELETE', $this->dbengine);
		$query->from(static::$tableNoPrefix);
		$query->where(static::$primaryKey . ' = ?', $this->stored[static::$primaryKey]);
		$query->limit(1);
		$stmt = $query->prepare();
		if (!$stmt->execute()) {
			$info = $stmt->errorInfo();
			$e = new QueryFailedException($info[2]);
			$e->errorInfo = $info;
			throw $e;
		}
		return true;
	}
}

?>