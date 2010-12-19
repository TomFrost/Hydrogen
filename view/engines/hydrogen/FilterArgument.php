<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\ExpressionEvaluator;

class FilterArgument {
	protected $data;

	public function __construct($data) {
		$this->data = &$data;
	}

	public function getPHPValue() {
		if (is_object($this->data))
			return $this->data->getVariablePHP();
		return $this->data;
	}
}

?>