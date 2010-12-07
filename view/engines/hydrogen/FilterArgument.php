<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\ExpressionEvaluator;

class FilterArgument {
	const TYPE_VARIABLE = 0;
	const TYPE_NATIVE = 1;

	protected $data;
	protected $type;

	public function __construct($type, $data) {
		$this->type = &$type;
		$this->data = &$data;
	}

	public function getValue($context) {
		if ($this->type === self::TYPE_VARIABLE)
			return ExpressionEvaluator::evalVariableString($this->data,
				$context);
		return $this->data;
	}
}

?>