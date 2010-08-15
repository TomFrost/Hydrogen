<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\Lexer;
use hydrogen\view\NodeList;
use hydrogen\view\exceptions\NoSuchTagException;
use hydrogen\view\exceptions\TemplateSyntaxException;

class Parser {
	protected $loader;
	protected $tokens;
	protected $cursor;
	protected $context;
	protected $originNodes;
	protected $originParent;

	public function __construct($viewName, $context, $loader) {
		$this->context = $context;
		$this->loader = $loader;
		$this->tokens = array();
		$this->cursor = 0;
		$this->originNodes = array();
		$this->originParent = array();
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
		$reachedUntil = false;
		$nodeList = new NodeList();
		for (; $this->cursor < count($this->tokens); $this->cursor++) {
			$token = $this->tokens[$this->cursor];
			switch ($token->type) {
				case Lexer::TOKEN_TEXT:
					$nodeList->addNode(
						new TextNode($token->data)
					);
					$this->originNodes[$token->origin] = true;
					break;
				case Lexer::TOKEN_VARIABLE:
					$nodeList->addNode(
						$this->getVariableNode($token->origin, $token->data)
					);
					$this->originNodes[$token->origin] = true;
					break;
				case Lexer::TOKEN_BLOCK:
					if (in_array($token->data, $untilBlock)) {
						$reachedUntil = true;
						break;
					}
					$node = $this->getBlockNode($token->origin, $token->data);
					if ($node) {
						$nodeList->addNode($node);
						$this->originNodes[$token->origin] = true;
					}
			}
		}
		if (is_array($untilBlock) && !$reachedUntil) {
			throw new NoSuchBlockException("Block(s) not found: " .
				implode(", ", $untilBlock));
		}
		return $nodeList;
	}
	
	public function incrementCursor($incBy=1) {
		$this->cursor += $incBy;
	}
	
	public function getTokenAtCursor() {
		if (isset($this->tokens[$this->cursor]))
			return $this->tokens[$this->cursor];
		return false;
	}
	
	public function originHasNodes($origin) {
		return isset($this->originNodes[$origin]);
	}
	
	public function getParent($origin) {
		if (!isset($this->originParent[$origin]))
			return false;
		return $this->originParent[$origin];
	}
	
	public function setOriginParent($origin, $parent) {
		$this->originParent[$origin] = $parent;
	}
	
	protected function getVariableNode($origin, $data) {
		$var = Lexer::getVariable($data, $filters);
		return new VariableNode($var, $filters, $this->context, $origin);
	}
	
	protected function getBlockNode($origin, $data) {
		$cmd = Lexer::getBlockCommand($data, $args);
		$class = '\hydrogen\view\tags\\' . $cmd . 'Tag';
		if (!@class_exists($class))
			throw new NoSuchTagException("Tag in $origin does not exist: $cmd");
		if ($class::MUST_BE_FIRST && $this->originHasNodes($origin))
			throw new TemplateSyntaxException("Tag must be first in template: $cmd");
		return $class::getNode($cmd, $args, $this, $this->context, $origin);
	}
}

?>