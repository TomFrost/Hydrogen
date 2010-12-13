<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\exceptions\MemberAlreadyExistsException;

class PHPFile {
	const PHP_OPENTAG = "<?php ";
	const PHP_CLOSETAG = " ?>";

	protected $contextDeclarations;
	protected $privateDeclarations;
	protected $functions;
	protected $content;

	public function __construct() {
		$this->contextDeclarations = array();
		$this->privateDeclarations = array();
		$this->functions = array();
		$this->content = '';
	}

	public function addContextDeclaration($name, $value) {
		if (isset($this->contextDeclarations[$name]))
			throw new MemberAlreadyExistsException(
				"A context variable named '$name' has already been declared.");
		$this->contextDeclarations[$name] = $value;
	}

	public function addPrivateDeclaration($name, $value) {
		if (isset($this->privateDeclarations[$name]))
			throw new MemberAlreadyExistsException(
				"A private variable named '$name' has already been declared.");
		$this->privateDeclarations[$name] = $value;
	}

	public function addFunction($name, $args, $code) {
		if (isset($this->functions[$name]))
			throw new MemberAlreadyExistsException(
				"A function named '$name' has already been declared.");
		$this->functions[$name] = array($args, $code);
	}

	public function addPageContent($mixed) {
		$this->content .= $mixed;
	}

	public function getPHP() {
		$page = self::PHP_OPENTAG;
		foreach ($this->contextDeclarations as $var => $val)
			$page .= '$context->' . $var . " = $val;";
		foreach ($this->privateDeclarations as $var => $val)
			$page .= '$' . $var . " = $val;";
		foreach ($this->functions as $name => $data) {
			$page .= "function $name(";
			if (is_array($data[0]))
				$page .= implode(', ', $data[0]);
			$page .= ") {$data[1]}";
		}
		$page .= self::PHP_CLOSETAG . $this->content;
		$page = str_replace(self::PHP_CLOSETAG . self::PHP_OPENTAG, '', $page);
		return $page;
	}
}

?>