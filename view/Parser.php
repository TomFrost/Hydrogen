<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\Lexer;
use hydrogen\view\NodeList;

class Parser {
	protected $loader;
	protected $tokens;
	protected $cursor;
	protected $nodeList;

	public function __construct($viewName, $loader) {
		$this->loader = $loader;
		$this->tokens = array();
		$this->cursor = 0;
		$this->nodeList = new NodeList();
		$this->addPage($viewName);
	}
	
	public function addPage($pageName) {
		$page = $this->loader->load($pageName);
		$pageTokens = Lexer::tokenize($page);
		$this->tokens = array_merge($this->tokens, $pageTokens);
	}
}

?>