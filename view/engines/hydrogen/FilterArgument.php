<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\nodes\VariableNode;

class FilterArgument {
	protected $data;

	public function __construct($data) {
		$this->data = &$data;
	}

	public function getValue($phpFile) {
		if (is_object($this->data)) {
			$var = new VariableNode(
				$this->data->varLevels,
				$this->data->filters,
				false,
				$this->data->origin);
			return $var->getVariablePHP($phpFile);
		}
		return "'" . str_replace("'", '\\\'', $this->data) . "'";
	}
}

?>