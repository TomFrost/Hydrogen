<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

class Token {
	public $type;
	public $data;
	
	public function __construct($type, $data) {
		$this->type = $type;
		$this->data = $data;
	}
}

?>