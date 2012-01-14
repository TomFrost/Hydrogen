<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\nodes\VariableNode;

/**
 * FilterArgument represents a single argument passed to a variable filter.
 * That argument can be a context variable or native type such as a string or
 * integer.  This class allows {@link \hydrogen\view\engines\hydrogen\Filter}
 * classes to be entirely agnostic of the contents of an argument.  Calling
 * the {@link getValue()} function will return pure PHP -- either a quoted
 * string or a variable -- that can be injected directly into the filter's
 * outputted code.
 */
class FilterArgument {
	protected $data;

	/**
	 * Creates a new FilterArgument containing the provided data.  Any
	 * PHP literals will be interpreted as strings, and any Hydrogen
	 * Context variables should be contained within a {@link VariableNode}
	 * object.
	 *
	 * @param mixed $data The data to contain in this argument
	 */
	public function __construct($data) {
		$this->data = &$data;
	}

	/**
	 * Gets the value of this argument, formatted in pure PHP code.  Will
	 * return the PHP necessary to get the contents of a context variable
	 * if this argument holds a variable, or a quoted string for most
	 * anything else.  No post-processing is required on the returned data
	 * to turn it into PHP code.
	 *
	 * @param \hydrogen\view\engines\hydrogen\PHPFile $phpFile The instance
	 * 		of PHPFile being used to render this template.  This is required
	 * 		to handle the rare case that this argument contains a VariableNode
	 * 		that has filters that need to be applied.
	 * @return string a string of pure PHP code that represents the contents of
	 * 		this argument.
	 */
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