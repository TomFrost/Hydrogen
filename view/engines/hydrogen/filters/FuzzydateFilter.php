<?php
/**
 * Copyright 2011 Koen Klaren
 * Made for use with Hydrogen, Copyright Tom Frost 2011
 * --------------------------
 * Converts a timestamp to a relative date
 * Returns:
 *      "just now"          if the difference is less then 1 minute
 *      "$n minutes ago"
 *      "an hour ago"
 *      "$n hours ago"
 *      "yesterday"
 *      "$n days ago"
 *      "a week ago"
 * After "a week ago" it returns the date formatted in a user defined format, default format is "\o\n j F Y"
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class FuzzydateFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
        // Function for parsing the date
        $phpfile->addFunction('fuzzydate', array('$date', '$format'), <<<'DATE'
            $difference = time() - (int)$date;
            
            if (($difference = round($difference / 60)) < 1)
                return "just now";
            elseif ($difference < 60)
                return $difference . " minutes ago";
            elseif (($difference = round($difference / 60)) == 1)
                return "an hour ago";
            elseif ($difference < 24)
                return $difference . " hours ago";
            elseif (($difference = round($difference / 24)) == 1)
                return "yesterday";
            elseif ($difference < 7)
                return ($difference) . " days ago";
            elseif (($difference = round($difference / 7)) == 1)
                return "a week ago";
            else
                return date($format, $date);
DATE
);
    
        // Get the format
        $format = ($args[0] ? $args[0]->getValues($phpfile) : '"\o\\\n j F Y"');
        
        return 'fuzzydate(' . $string . ', ' . $format . ')';
	}

}
?>