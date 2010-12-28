<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\TextNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class TemplatetagTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$args = strtolower($args);
		switch ($args) {
			case 'openblock':
				return new TextNode(Lexer::BLOCK_OPENTAG, $origin);
			case 'closeblock':
				return new TextNode(Lexer::BLOCK_CLOSETAG, $origin);
			case 'openvariable':
				return new TextNode(Lexer::VARIABLE_OPENTAG, $origin);
			case 'closevariable':
				return new TextNode(Lexer::VARIABLE_CLOSETAG, $origin);
			case 'opencomment':
				return new TextNode(Lexer::COMMENT_OPENTAG, $origin);
			case 'closecomment':
				return new TextNode(Lexer::COMMENT_CLOSETAG, $origin);
		}
		throw new TemplateSyntaxException('Illegal tag type "' . $args .
			'" used in the "templatetag" tag in the "' . $origin .
			'" template.');
	}

}

?>