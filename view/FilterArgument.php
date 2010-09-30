<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

class FilterArgument {
	const TYPE_VARIABLE = 0;
	const TYPE_NATIVE = 1;

	public $data;
	public $type;

	public function __construct($type, $data) {
		$this->type = &$type;
		$this->data = &$data;
	}
}

?>