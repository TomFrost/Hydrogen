<?php
/**
 * Longtoip Filter by AwesomezGuy
 *
 * I wanted to put a cool Copyright (C) AwesomezGuy 2011 here,
 * but that might interfere with TomFrost accepting this into 
 * the Hydrogen GitHub :/
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;

class LongtoipFilter implements Filter {

        public static function applyTo($string, $args, &$escape, $phpfile) {
                return "long2ip($string)";
        }
}

?>