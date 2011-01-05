<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\exceptions\MemberAlreadyExistsException;

/**
 * The PHPFile is an object that accepts different types of PHP code
 * (declarations, functions, mixed HTML/PHP in the body, etc.) and outputs
 * a full PHP page when complete.  It adds a levels of convenience, as a single
 * instance of PHPFile can be passed to all the classes that need to add
 * something to the page, and PHPFile takes care of putting the code in the
 * right places, in the right tags, and ensuring no function or variable
 * names are overwritten accidentally.
 */
class PHPFile {
	/**
	 * A string representing an open-tag for PHP code.
	 */
	const PHP_OPENTAG = "<?php ";
	
	/**
	 * A string representing a close-tag for PHP code.
	 */
	const PHP_CLOSETAG = " ?>";

	protected $contextDeclarations;
	protected $privateDeclarations;
	protected $functions;
	protected $content;

	/**
	 * Creates a new instance of PHPFile.
	 */
	public function __construct() {
		$this->contextDeclarations = array();
		$this->privateDeclarations = array();
		$this->functions = array();
		$this->content = '';
	}

	/**
	 * Adds a new variable declaration within the template context.  Any
	 * variables declared using this method will be available to the entire
	 * template (including other templates involved via extensions, the include
	 * tag, etc) if used in a variable tag.
	 *
	 * Tags and filters can also access the contents of context variables
	 * via the private variable $context, which is an instance of
	 * {@link \hydrogen\view\ContextStack}.
	 *
	 * @param string $name The name of the variable to declare
	 * @param string $value The PHP representation of the value to which the
	 * 		variable should be set.  For example, if the variable should
	 * 		be initialized to "hello", $value should be "hello" <em>with
	 * 		the quotes</em> as that is proper PHP.  Likewise, if the value
	 * 		is an array, $value should be array('values', 'here').
	 * @param boolean $override true if this function should override any
	 * 		previously-set context variable with the same name, false if
	 * 		setting a variable with the same name to a different value
	 * 		should trigger an exception.  If not specified, the default value
	 * 		is false.
	 * @throws MemberAlreadyExistsException if a variable with the same name
	 * 		but a different value has already been set, and $override is
	 * 		false or not specified.
	 */
	public function addContextDeclaration($name, $value, $override=false) {
		if (!$override && isset($this->contextDeclarations[$name]) &&
				$this->contextDeclarations[$name] != $value) {
			throw new MemberAlreadyExistsException(
				'A different context variable named "' . $name .
				'" has already been declared.');
		}
		$this->contextDeclarations[$name] = $value;
	}

	/**
	 * Adds a new private variable declaration to the top of the template.  Any
	 * variables declared using this method will be available to the entire
	 * template, <em>excluding</em> templates that have been included through
	 * the include tag used with a variable.  Variable includes are parsed
	 * entirely separately with their own instance of PHPFile, so if a variable
	 * is needed to carry over into these templates, a context variable
	 * declaration must be used.
	 *
	 * Private variable declarations are normal $varName variables inside the
	 * PHP file, and can be used by any tag or filter within the template.  The
	 * variables are not, however, accessible to the template designer using
	 * variable tags, so they are convenient for keeping track of information
	 * that the template designer should not have the ability to output or
	 * alter.
	 *
	 * @param string $name The name of the variable to declare
	 * @param string $value The PHP representation of the value to which the
	 * 		variable should be set.  For example, if the variable should
	 * 		be initialized to "hello", $value should be "hello" <em>with
	 * 		the quotes</em> as that is proper PHP.  Likewise, if the value
	 * 		is an array, $value should be array('values', 'here').
	 * @param boolean $override true if this function should override any
	 * 		previously-set private variable with the same name, false if
	 * 		setting a variable with the same name to a different value
	 * 		should trigger an exception.  If not specified, the default value
	 * 		is false.
	 * @throws MemberAlreadyExistsException if a variable with the same name
	 * 		but a different value has already been set, and $override is
	 * 		false or not specified.
	 */
	public function addPrivateDeclaration($name, $value, $override=false) {
		if (!$override && isset($this->privateDeclarations[$name]) &&
				$this->privateDeclarations[$name] != $value) {
			throw new MemberAlreadyExistsException(
				'A different private variable named "' . $name .
				'" has already been declared.');
		}
		$this->privateDeclarations[$name] = $value;
	}

	/**
	 * Adds a new function declaration to the top of the template.  Any
	 * functions declared using this method will be available to the entire
	 * template <em>excluding</em> templates that have been included through
	 * the include tag used with a variable.  Variable includes are parsed
	 * entirely separately with their own instance of PHPFile, and will need
	 * their own copy of the function.  For Hydrogen templates, this should
	 * all happen automatically.
	 *
	 * @param string $name The name of the function to declare.
	 * @param array $args An array of strings defining the argument's
	 * 		functions.  Each argument should be preceeded by the usual PHP
	 * 		dollar sign ($) character.
	 * @param string $code The PHP code making up the contents of the function.
	 * 		This code should not be wrapped in curly braces { }.
	 * @param boolean $override true if this function should override any
	 * 		previously-set template function with the same name, arguments, and
	 * 		code, false if setting a function with the same name to different
	 * 		values should trigger an exception.  If not specified, the default
	 * 		value is false.
	 */
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

	/**
	 * Adds content to the body of the page.  This content can be plaintext
	 * mixed with PHP, as long as the appropriate opening and closing PHP tags
	 * are used.  Each time this function is called, the supplied string will
	 * be appended to the page content already submitted through this function.
	 *
	 * Rather than hardcoding <?php ?> tags in added strings, it is better
	 * practice to use PHPFile::PHP_OPENTAG and PHPFile::PHP_CLOSETAG.  This
	 * ensures that, should these delimiters change in the future or if
	 * Hydrogen allows for more customized PHP support, code written to use
	 * PHPFile need not be changed.
	 *
	 * @param string $mixed The content to add to the page body.
	 */
	public function addPageContent($mixed) {
		$this->content .= $mixed;
	}

	/**
	 * Compiles together all data submitted in this instance of PHPFile and
	 * returns a single string representing all the executable PHP code
	 * acquired thus far.  As long as all code submitted has been syntactically
	 * correct and error-free, the output of this function should be suitable
	 * to pass to PHP's eval() function or write to a .php file.
	 *
	 * @return string A string of executable PHP code built from the
	 * 		information supplied to this instance of PHPFile.
	 */
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
				$page .= 'function ' . $name . '(';
				if (is_array($data[0]))
					$page .= implode(', ', $data[0]);
				$page .= ') { ' . $data[1] . '}';
			}
			$page .= self::PHP_CLOSETAG;
		}
		$page .= $this->content;
		$page = str_replace(self::PHP_CLOSETAG . self::PHP_OPENTAG, '', $page);
		return $page;
	}
}

?>