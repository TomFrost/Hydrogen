<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

abstract class Token {
	const TOKEN_TYPE = 0;
	
	public $origin;
	public $raw;
	
	public function __construct($origin, $raw) {
		$this->origin = &$origin;
		$this->raw = &$raw;
	}
}

?>