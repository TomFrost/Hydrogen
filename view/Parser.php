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

	public function __construct($viewName, $loader) {
		$this->loader = $loader;
		$this->tokens = array();
		$this->cursor = 0;
		$this->addPage($viewName);
	}
	
	public function addPage($pageName) {
		$page = $this->loader->load($pageName);
		$pageTokens = Lexer::tokenize($pageName, $page);
		array_splice($this->tokens, $this->cursor, 0, $pageTokens);
	}
	
	public function parse($untilBlock=false) {
		if ($untilBlock !== false && !is_array($untilBlock))
			$untilBlock = array($untilBlock);
		$nodeList = new NodeList();
		for (; $this->cursor < count($this->tokens); $this->cursor++) {
			switch ($this->tokens[$this->cursor]->type) {
				case Lexer::TOKEN_TEXT:
					$nodeList->addNode(
						new TextNode($this->tokens[$this->cursor]->data)
					);
					break;
				case Lexer::TOKEN_VARIABLE:
					$nodeList->addNode(
						new VariableNode($this->tokens[$this->cursor]->data)
					);
					break;
				case Lexer::TOKEN_BLOCK:
					$nodeList->addNode(
						$this->getBlockNode($this->tokens[$this->cursor]->data)
					);
			}
		}
		return $nodeList;
	}
	
	protected function getBlockNode($data) {
		
	}
}

?>