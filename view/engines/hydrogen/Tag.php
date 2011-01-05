<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

/**
 * Tags in the Hydrogen Templating Engine are blocks of simple code or
 * complex logic that can be executed with a string inside of a template.
 * Template tags have two parts: The first is the Tag class itself (or, more
 * accurately, a class extending this abstract Tag class) and acts as an
 * extension of the Hydrogen {@link \hydrogen\view\engines\hydrogen\Parser}
 * class.  It's called when a tag is encountered in the template, is given
 * the information about the tag with a reference to the Parser instance, and
 * is expected to utilize those tools to produce the second part of a tag:
 * the {@link \hydrogen\view\engines\hydrogen\Node}.
 *
 * Nodes are independent bits of the parsed template that can be called upon
 * to produce raw PHP code.  When executed, that PHP code should produce the
 * output expected for its part of the template.  The Tag's role is simply to
 * create and return a Node instance (or, again more accurately, an instance
 * of a class implementing the Node interface), or boolean false if the tag
 * should produce no output in the template itself.
 *
 * To achieve this goal, Tags are allowed to parse through the tag arguments
 * as well as utilize the Parser instance (including calling the Parser's
 * parse() method to collect other Nodes) to collect and format all data
 * necessary to instantiate the appropriate Node instance.
 */
abstract class Tag {
	
	/**
	 * Gets an instance of a class implementing
	 * {@link \hydrogen\view\engines\hydrogen\Node}, or false if the Tag
	 * determines that no output is required.
	 *
	 * @param string $cmd The command that triggered this call
	 * @param string $args The arguments that were supplied in the template
	 * 		tag
	 * @param \hydrogen\view\engines\hydrogen\Parser The instance of Parser
	 * 		that's handling the parsing of this template
	 * @param string $origin The name of the template containing the call
	 * 		to this Tag.
	 * @returns \hydrogen\view\engines\hydrogen\Node|boolean An instance
	 * 		of a Node responsible for generating the output this Tag requires,
	 * 		or boolean false if no template output is necessary.
	 */
	public abstract static function getNode($cmd, $args, $parser, $origin);

	/**
	 * Determines whether this tag must be used before the particular template
	 * using it (the template currently being parsed, whether it be an extended
	 * template, include, or anything else) has produced any Nodes.  It is
	 * extremely rare that this function will need to be overridden, and in the
	 * cases where that is appropriate, it is rarely necessary to include any
	 * more logic than "return true".
	 *
	 * @return boolean true if this Tag must be used before a particular
	 * 		template has gained any Nodes, false otherwise.
	 */
	public static function mustBeFirst() {
		return false;
	}
}

?>