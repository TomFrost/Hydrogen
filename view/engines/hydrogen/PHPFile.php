<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
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

	public function addContextDeclaration($name, $value, $override=false) {
		if (!$override && isset($this->contextDeclarations[$name]) &&
				$this->contextDeclarations[$name] != $value) {
			throw new MemberAlreadyExistsException(
				'A different context variable named "' . $name .
				'" has already been declared.');
		}
		$this->contextDeclarations[$name] = $value;
	}

	public function addPrivateDeclaration($name, $value, $override=false) {
		if (!$override && isset($this->privateDeclarations[$name]) &&
				$this->privateDeclarations[$name] != $value) {
			throw new MemberAlreadyExistsException(
				'A different private variable named "' . $name .
				'" has already been declared.');
		}
		$this->privateDeclarations[$name] = $value;
	}

	public function addFunction($name, $args, $code, $override=false) {
		if (!$override && isset($this->functions[$name]) &&
				$this->functions[$name][0] != $args &&
				$this->functions[$name][1] != $code) {
			throw new MemberAlreadyExistsException(
				'A different function named "' . $name .
				'" has already been declared.');
		}
		$this->functions[$name] = array($args, $code);
	}

	public function addPageContent($mixed) {
		$this->content .= $mixed;
	}

	public function getPHP() {
		$page = '';
		if (count($this->contextDeclarations) +
				count($this->privateDeclarations) +
				count($this->functions) > 0) {
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
			$page .= self::PHP_CLOSETAG;
		}
		$page .= $this->content;
		$page = str_replace(self::PHP_CLOSETAG . self::PHP_OPENTAG, '', $page);
		return $page;
	}
}

?>