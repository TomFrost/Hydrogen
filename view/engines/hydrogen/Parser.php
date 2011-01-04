<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\HydrogenEngine;
use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\NodeArray;
use hydrogen\view\engines\hydrogen\nodes\TextNode;
use hydrogen\view\engines\hydrogen\nodes\VariableNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

/**
 * The Parser layer of the Hydrogen Templating Engine is responsible for
 * getting the tokenized version of a template file from the {@link Lexer}
 * and iterating through the tokens, parsing each one into a
 * {@link \hydrogen\view\engines\hydrogen\Node} that can later be used to
 * generate the raw PHP code of the compiled template.
 */
class Parser {
	protected $loader;
	protected $tokens;
	protected $originNodes;
	protected $originParent;
	protected $objs;
	protected $stacks;

	/**
	 * Creates a new Parser object.
	 *
	 * @param string $viewName The name of the template that the Parser is
	 * 		being instantiated to load.
	 * @param \hydrogen\view\Loader $loader The loader to be used to retrieve
	 * 		the contents of the specified template, and any other template that
	 * 		may need to be loaded within this parsing process.
	 */
	public function __construct($viewName, $loader) {
		$this->loader = $loader;
		$this->originNodes = array();
		$this->originParent = array();
		$this->stacks = array();
		$this->tokens = $this->getTokensForPage($viewName);
	}

	/**
	 * Gets the tokens for any given template name.
	 *
	 * @param string $pageName The name of the template for which tokens should
	 * 		be retrieved.
	 * @return array An array of {@link Token} objects representing the
	 * 		specified template.
	 * @throws \hydrogen\view\exceptions\NoSuchViewException if the specified
	 * 		template is not found.
	 */
	protected function getTokensForPage($pageName) {
		$page = $this->loader->load($pageName);
		return Lexer::tokenize($pageName, $page);
	}

	/**
	 * Appends a template's tokens to the end of the current token array.  This
	 * is useful for including templates that have blocks that need to override
	 * previously-set blocks.
	 *
	 * @param string $pageName The name of the template whose tokens should be
	 * 		loaded and appended to the token array.
	 * @throws \hydrogen\view\exceptions\NoSuchViewException if the specified
	 * 		template is not found.
	 */
	public function appendPage($pageName) {
		$pageTokens = $this->getTokensForPage($pageName);

		// array_merge and array_splice take too much ram due to the
		// duplication of array data.  Combining array_shift with array_push
		// maintains current RAM usage.
		while ($val = array_shift($pageTokens))
			array_push($this->tokens, $val);
	}

	/**
	 * Prepends a template's tokens to the beginning of the current token
	 * array.  This is useful for including templates that should be injected
	 * into the page at the exact spot currently being parsed.
	 *
	 * @param string $pageName The name of the template whose tokens should be
	 * 		loaded and prepended to the token array.
	 * @throws \hydrogen\view\exceptions\NoSuchViewException if the specified
	 * 		template is not found.
	 */
	public function prependPage($pageName) {
		$pageTokens = $this->getTokensForPage($pageName);

		// array_merge and array_splice take too much ram due to the
		// duplication of array data.  Combining array_pop with array_unshift
		// maintains current RAM usage.
		while ($val = array_pop($pageTokens))
			array_unshift($this->tokens, $val);
	}

	/**
	 * Iterates through the token array and turns each token (with some
	 * exceptions) into a {@link \hydrogen\view\engines\hydrogen\Node} object
	 * capable of outputting the raw PHP necessary to create that bit of the
	 * template.  This function returns a {@link NodeArray} object, which can
	 * be treated much like a native PHP array but allows all the contained
	 * nodes to be rendered simultaneously with one function call.
	 *
	 * Optionally, the $untilBlock argument can be passed which instructs this
	 * function to stop parsing when it reaches a certain block command.  This
	 * is useful for when tags need to parse up until an ending tag.  The tag
	 * is given a NodeArray of all the nodes it contains, and when the original
	 * parser call resumes execution, those tokens have already been shifted
	 * off of the token array.
	 *
	 * Note that, when $untilBlock is used, this function will parse up
	 * <em>until</em> that tag is reached, but will not parse that tag.  If
	 * the parser should not attempt to parse that tag in the future, consider
	 * calling {@link skipNextToken()} after this funciton is called.
	 *
	 * @param string|array $untilBlock A tag command or array of tag commands
	 * 		at which to stop the parser loop.  This is optional, and the
	 * 		default is for the loop to run until all the tokens are parsed.
	 * @returns NodeArray A NodeArray of all parsed nodes.
	 * @throws NoSuchBlockException when the function reaches the end of the
	 * 		token array and has no found any of the tag commands specified in
	 * 		$untilBlock.
	 * @throws TemplateSyntaxException if a syntax error is experienced in a
	 * 		block token.
	 */
	public function parse($untilBlock=false) {
		if ($untilBlock !== false && !is_array($untilBlock))
			$untilBlock = array($untilBlock);
		$reachedUntil = false;
		$nodes = new NodeArray();
		while ($token = array_shift($this->tokens)) {
			switch ($token::TOKEN_TYPE) {
				case Lexer::TOKEN_TEXT:
					$nodes[] = new TextNode($token->raw, $token->origin);
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

	/**
	 * Instructs the parser to skip the next token in the token array, so that
	 * it doesn't attempt to parse it.  This is commonly used in tags that need
	 * to parse up until a certain
	 * {@link \hydrogen\view\engines\hydrogen\tokens\BlockToken}, and then need
	 * to instruct the parser that it should not attempt to process that token.
	 */
	public function skipNextToken() {
		array_shift($this->tokens);
	}

	/**
	 * Retrieves the next token in the token array without removing it from
	 * the parsing queue.
	 *
	 * @returns \hydrogen\view\engines\hydrogen\Token|boolean the next token
	 * 		in the queue, or false if the queue is empty.
	 */
	public function peekNextToken() {
		return isset($this->tokens[0]) ? $this->tokens[0] : false;
	}

	/**
	 * Checks to see if a certain origin (or template name) has produced any
	 * parsed nodes.
	 *
	 * @param string $origin The name of the template to check for parsed
	 * 		nodes.
	 * @returns boolean true if the given origin has nodes, or false if it
	 * 		does not (yet).
	 */
	public function originHasNodes($origin) {
		return isset($this->originNodes[$origin]);
	}

	/**
	 * Gets the immediate parent of a certain template, if that template has
	 * been parsed.  The parent is defined as the template being extended
	 * using the {@link \hydrogen\view\engines\hydrogen\tags\ExtendsTag} in
	 * the template body.
	 *
	 * @param string $origin The name of the template for which to retrieve
	 * 		the name of the parent template.
	 * @return string|boolean the name of the parent template, or false if
	 * 		the template either does not extend another template or has not yet
	 * 		been parsed.
	 */
	public function getParent($origin) {
		if (!isset($this->originParent[$origin]))
			return false;
		return $this->originParent[$origin];
	}

	/**
	 * Defines the parent of a template.  Note that calling this function
	 * alone has no impact on what this template physically extends; it simply
	 * informs Parser of the change and affects the parent reported by the
	 * {@link getParent()} function.
	 *
	 * @param string $origin The template of which to alter the parent reported
	 * 		by {@link getParent()}.
	 */
	public function setOriginParent($origin, $parent) {
		$this->originParent[$origin] = $parent;
	}

	/**
	 * Registers any object with any key in this parser.  This acts as a
	 * convenient key/value store allowing tags to keep track of information
	 * between calls, and is most useful for defined Blocks to allow themselves
	 * to be overwritten and call their parent blocks.
	 *
	 * @param string $key The key name under which to store the object.
	 * @param mixed $val The value to store in the Parser.
	 */
	public function registerObject($key, $val) {
		$this->objs[$key] = $val;
	}

	/**
	 * Retrieves an object that has been stored in the Parser using the
	 * {@link registerObject()} method.
	 *
	 * @param string $key The key name for which to retrieve the value.
	 * @return mixed The value stored under the given key name, or boolean
	 * 		false if the key does not exist.
	 */
	public function getObject($key) {
		return isset($this->objs[$key]) ? $this->objs[$key] : false;
	}
	
	/**
	 * Retrieves the value at the top of a specified stack stored in the
	 * Parser, without popping it from the stack.
	 *
	 * @param string $stack The name of the parser-stored stack to peek.
	 * @return mixed The value currently on top of the specified stack, or NULL
	 * 		if the stack is empty or does not exist.
	 */
	public function stackPeek($stack) {
		if (isset($this->stacks[$stack]) && count($this->stacks[$stack]) > 0)
			return $this->stacks[$stack][count($this->stacks[$stack]) - 1];
		return null;
	}
	
	/**
	 * Pushes a certain value onto a Parser-stored stack.  If the specified
	 * stack does not exist, it will be created.
	 *
	 * @param string $stack The name of the stack to which the value should be
	 * 		pushed on top.
	 * @param mixed $value The value to store in the specified stack.
	 */
	public function stackPush($stack, $value) {
		if (!isset($this->stacks[$stack]))
			$this->stacks[$stack] = array($value);
		else
			$this->stacks[$stack][] = $value;
	}
	
	/**
	 * Pops the top value off of a specified stack.
	 *
	 * @param string $stack The name of the Parser-stored stack from which to
	 * 		pop the topmost value.
	 * @return mixed The value that was popped from the stop of the specified
	 * 		stack, or NULL if the stack is empty or does not exist.
	 */
	public function stackPop($stack) {
		if (isset($this->stacks[$stack]))
			return array_pop($this->stacks[$stack]);
		return null;
	}

	/**
	 * Gets a block node from the specified tag command by searching for the
	 * tag class, instructing the tag to process the supplied data, and
	 * returning the resulting {@link \hydrogen\view\engines\hydrogen\Node}.
	 *
	 * Note that "BlockNode" can refer to two different things.  This function
	 * produces a node from any generic block tag; it does not retrieve an
	 * instance of {@link \hydrogen\view\engines\hydrogen\nodes\BlockNode}
	 * unless such a result would be appropriate given the tag command.
	 *
	 * @param string $origin The template name in which the block tag was
	 * 		found.
	 * @param string $cmd The tag command for which to produce a node.
	 */
	protected function getBlockNode($origin, $cmd, $args) {
		$class = HydrogenEngine::getTagClass($cmd, $origin);
		if ($class::mustBeFirst() && $this->originHasNodes($origin)) {
			throw new TemplateSyntaxException(
				"Tag must be first in template: $cmd");
		}
		return $class::getNode($cmd, $args, $this, $origin);
	}
}

?>