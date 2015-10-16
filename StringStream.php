<?php

namespace Tale\Http;

class StringStream extends Stream
{

    public function __construct($initialContent = null, $memoryOnly = false)
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