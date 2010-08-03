<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\exceptions\NoSuchViewException;

class TemplateLoader {
	private $templatePath;
	
	public function __construct($templatePath) {
		$this->templatePath = $templatePath;
	}
	
	public function display() {
		
	}
}

?>