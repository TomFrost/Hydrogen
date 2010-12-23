<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\NodeArray;
use hydrogen\view\engines\hydrogen\nodes\TextNode;
use hydrogen\view\engines\hydrogen\nodes\VariableNode;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchTagException;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class Parser {
	protected $loader;
	protected $tokens;
	protected $originNodes;
	protected $originParent;
	protected $objs;
	protected $stacks;

	public function __construct($viewName, $loader) {
		$this->loader = $loader;
		$this->originNodes = array();
		$this->originParent = array();
		$this->stacks = array();
		$this->tokens = $this->getTokensForPage($viewName);
	}

	protected function getTokensForPage($pageName) {
		$page = $this->loader->load($pageName);
		return Lexer::tokenize($pageName, $page);
	}

	public function appendPage($pageName) {
		$pageTokens = $this->getTokensForPage($pageName);

		// array_merge and array_splice take too much ram due to the
		// duplication of array data.  Combining array_shift with array_push
		// maintains current RAM usage.
		while ($val = array_shift($pageTokens))
			array_push($this->tokens, $val);
	}

	public function prependPage($pageName) {
		$pageTokens = $this->getTokensForPage($pageName);

		// array_merge and array_splice take too much ram due to the
		// duplication of array data.  Combining array_pop with array_unshift
		// maintains current RAM usage.
		while ($val = array_pop($pageTokens))
			array_unshift($this->tokens, $val);
	}

	public function parse($untilBlock=false) {
		if ($untilBlock !== false && !is_array($untilBlock))
			$untilBlock = array($untilBlock);
		$reachedUntil = false;
		$nodes = new NodeArray();
		while ($token = array_shift($this->tokens)) {
			switch ($token::TOKEN_TYPE) {
				case Lexer::TOKEN_TEXT:
					$nodes[] = new TextNode($token->raw);
					$this->originNodes[$token->origin] = true;
					break;
				case Lexer::TOKEN_VARIABLE:
					$nodes[] = new VariableNode($token->varLevels,
						$token->filters,
						$this->stackPeek('autoescape'),
						$token->origin);
					$this->originNodes[$token->origin] = true;
					break;
				case Lexer::TOKEN_BLOCK:
					if ($untilBlock !== false &&
							in_array($token->cmd, $untilBlock)) {
						$reachedUntil = true;
						array_unshift($this->tokens, $token);
						break;
					}
					$node = $this->getBlockNode($token->origin, $token->cmd,
						$token->args);
					if ($node) {
						$nodes[] = $node;
						$this->originNodes[$token->origin] = true;
					}
			}
			if ($reachedUntil === true)
				break;
		}
		if ($untilBlock !== false && !$reachedUntil) {
			throw new NoSuchBlockException("Block(s) not found: " .
				implode(", ", $untilBlock));
		}
		return $nodes;
	}

	public function skipNextToken() {
		array_shift($this->tokens);
	}

	public function peekNextToken() {
		return isset($this->tokens[0]) ? $this->tokens[0] : false;
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

	public function registerObject($key, $val) {
		$this->objs[$key] = $val;
	}

	public function getObject($key) {
		return isset($this->objs[$key]) ? $this->objs[$key] : false;
	}
	
	public function stackPeek($stack) {
		if (isset($this->stacks[$stack]))
			return $this->stacks[$stack][count($this->stacks[$stack]) - 1];
		return null;
	}
	
	public function stackPush($stack, $value) {
		if (!isset($this->stacks[$stack]))
			$this->stacks[$stack] = array($value);
		else
			$this->stacks[$stack][] = $value;
	}
	
	public function stackPop($stack) {
		if (isset($this->stacks[$stack]))
			return array_pop($this->stacks[$stack]);
		return null;
	}

	protected function getBlockNode($origin, $cmd, $args) {
		$class = '\\' . __NAMESPACE__ . '\tags\\' .
			ucfirst(strtolower($cmd)) . 'Tag';
		if (!@class_exists($class))
			throw new NoSuchTagException("Tag in template \"$origin\" does not exist: $cmd");
		if ($class::mustBeFirst() && $this->originHasNodes($origin)) {
			throw new TemplateSyntaxException(
				"Tag must be first in template: $cmd");
		}
		return $class::getNode($cmd, $args, $this, $origin);
	}
}

?>