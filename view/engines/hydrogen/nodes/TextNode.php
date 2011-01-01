<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\config\Config;
use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\PHPFile;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class TextNode implements Node {
	protected $text;
	protected $origin;

	public function __construct($text, $origin) {
		$this->text = $text;
		$this->origin = $origin;
	}

	public function render($phpFile) {
		if (!Config::getVal('view', 'allow_php') &&
				strpos($this->text, PHPFile::PHP_OPENTAG)) {
			throw new TemplateSyntaxException('Template "' . $this->origin .
				'" contains raw PHP code, which has been disallowed in the autoconfig file.');
		}
		$phpFile->addPageContent($this->text);
	}
}

?>