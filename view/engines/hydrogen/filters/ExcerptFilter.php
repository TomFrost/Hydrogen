<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

/**
 * The ExcerptFilter creates an excerpt of a given variable. It accepts two
 * optional arguments, the first being the excerpt length (default: 20) and the
 * second being the excerpt 'mode' (default: words).
 * It currently supports three modes:
 * - words
 * - lines
 * - characters
 * If a variable is actually shortened by generating the excerpt, it is
 * appended with "[...]".
 * Example:
 * <pre>
 * {% set myVar %}
 *     This is a very long Text.
 * {% endset %}
 * {{myVar|excerpt:5:w}} => This is a very long [...]
 * {{myVar|excerpt:12:c}} => This is a ve [...]
 * {{myVar|excerpt:3:l}} => This is a very long Text.
 * </pre>
 * As you can see, the third case is not appended with an ellipsis, as the
 * filtered string is not shorter than the input.
 */
class ExcerptFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$phpfile->addFunction('excerptFilter',
                        array('$str', '$num', '$needle', '$esc'), <<<'PHP'
                        $str = trim($str);
			$strlen = strlen( $str );
                        if($strlen==0) return utf8_encode($str);
                        $findpos = 0;
                        $cutpos = 0;
                        $steps = 0;
                        if($needle==false) {
                            $cutpos = $num;
                        } else {
                            while( $steps < $num && $findpos!==false) {
                                $cutpos = $findpos;
                                $findpos = strpos( $str, $needle, $findpos + 1 );
                                $steps++;
                            }
                        }
                        if($cutpos === false || $steps<$num)
                            return utf8_encode($str);
                        $ellipsis = ( $strlen > $cutpos ) ? ' [...]' : '';
                        return utf8_encode( substr( $str, 0, $cutpos ) . 
                            $ellipsis);
PHP
		);
                $num = (isset($args[0])) ? $args[0]->getValue($phpfile) : 20;
                $mode = (isset($args[1])) ? $args[1]->getValue($phpfile) : 'w';
                $needle = false;
                switch($mode) {
                    case 'l':
                        $needle = "\n";
                        break;
                    case 'c':
                        $needle = false;
                        break;
                    case 'w':
                    default:
                        $needle = ' ';
                }
                $string = 'excerptFilter(' . $string . ',' .
                    $num . ',"' . $needle . '",' .
                    ($escape ? 'true' : 'false') . ')';
		$escape = false;
		return $string;
	}

}""

?>