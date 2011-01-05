<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

/**
 * The Token abstract defines a token that the Hydrogen Templating Engine's
 * {@link \hydrogen\view\engines\hydrogen\Lexer} can break a template's
 * contents into.
 */
abstract class Token {
	const TOKEN_TYPE = 0;

	public $origin;
	public $raw;

	/**
	 * Creates a new instance of the Token class.
	 *
	 * @param string $origin The name of the template from which this token
	 * 		was generated.
	 * @param string $raw The raw text of this token, not parsed or modified
	 * 		in any way.
	 */
	public function __construct($origin, $raw) {
		$this->origin = &$origin;
		$this->raw = &$raw;
	}
}

?>