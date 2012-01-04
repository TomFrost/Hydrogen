<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\recache;

class TimeQueue {
    
    private $queue, $limit, $len, $head;
    
    function TimeQueue($size, $seconds) {
        $this->queue = array();
        $this->len = $size;
        $this->limit = $seconds;
        $this->head = $size - 1;
    }
    
    public function hit() {
        // Move the head forward one space
        $this->head = ($this->head + $this->len + 1) % $this->len;
        
        // Post the time
        $this->queue[$this->head] = time();
        
        // The tail is one after the head
        $tail = ($this->head + $this->len + 1) % $this->len;
        
        // Find out if the tail is within our limit range.  If so, return true.
        return isset($this->queue[$tail]) &&
            (time() - $this->queue[$tail] <= $this->limit);
    }
}

?>