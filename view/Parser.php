<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\Lexer;
use hydrogen\view\NodeList;
use hydrogen\view\exceptions\NoSuchTagException;

class Parser {
	protected $loader;
	protected $tokens;
	protected $cursor;
	protected $context;

	public function __construct($viewName, $context, $loader) {
		$this->context = $context;
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
						$this->getVariableNode(
							$this->tokens[$this->cursor]->origin,
							$this->tokens[$this->cursor]->data
						)
					);
					break;
				case Lexer::TOKEN_BLOCK:
					if (in_array($this->tokens[$this->cursor]->data,
							$untilBlock))
						break;
					$nodeList->addNode(
						$this->getBlockNode(
							$this->tokens[$this->cursor]->origin,
							$this->tokens[$this->cursor]->data
						)
					);
			}
		}
		return $nodeList;
	}
	
	public function incrementCursor($incBy=1) {
		$this->cursor += $incBy;
	}
	
	public function getTokenAtCurson() {
		return $this->tokens[$this->cursor];
	}
	
	protected function getVariableNode($origin, $data) {
		$var = Lexer::getVariable($data, $filters);
		return new VariableNode($var, $filters, $origin);
	}
	
	protected function getBlockNode($origin, $data) {
		$cmd = Lexer::getBlockCommand($data, $args);
		$class = '\hydrogen\view\tags\\' . $cmd . 'Tag';
		if (!@class_exists($class))
			throw new NoSuchTagException("Tag in $origin does not exist: $cmd");
		return $class::getNode($cmd, $args, $this, $origin);
	}
}

?>