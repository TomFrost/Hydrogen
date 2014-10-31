<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\TextNode;
use hydrogen\config\Config;

class IconTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
            $i = (is_array($args))?$args[0]:$args;
            $icon = sprintf("%s/%s/%s.png",
                    Config::getVal("general", "app_url"),
                    Config::getVal("misc","icondir"),
                    $i);
            return new TextNode($icon, $origin);
	}

}

?>