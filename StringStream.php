<?php

namespace Tale\Http;

use Tale\Http\Stream;

class StringStream extends Stream
{

    public function __construct($initialContent = null, $memoryOnly = true)
    {
        parent::__construct(
            $memoryOnly ? self::MEMORY : self::TEMP
        );

        if ($initialContent) {

            $this->write($initialContent);
            $this->rewind();
        }
    }
}