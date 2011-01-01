<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\database;

use hydrogen\database\DatabaseEngineFactory;

abstract class QueryFormatter {
	protected $verb, $queryArray;
	
	abstract public function __construct($queryArray);
	abstract public function getPreparedQuery();
	abstract public function getPreparedValues($valueArray=false);
	abstract public function getCompleteQuery($valueArray=false);
	
	protected function escapeString($string) {
		$engine = DatabaseEngineFactory::getEngine();
		return $engine->quote($string);
	}
}

?>