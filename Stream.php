<?php

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{

    const TEMP = 'php://temp';
    const MEMORY = 'php://memory';
    const INPUT = 'php://input';
    const OUTPUT = 'php://output';

    const DEFAULT_CONTEXT = self::TEMP;
    const DEFAULT_MODE = 'rb+';

    private $_context;
    private $_mode;
    private $_data;

    public function __construct($context = null, $mode = null)
    {

        $this->_context = $context ? $context : self::DEFAULT_CONTEXT;
        $this->_mode = $mode ? $mode : self::DEFAULT_MODE;

        if (is_string($this->_context))
            $this->_context = fopen($this->_context, $this->_mode);

        if (!is_resource($this->_context))
            throw new InvalidArgumentException(
                "Argument 1 needs to be resource or path/URI"
            );

        $this->_data = stream_get_meta_data($this->_context);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {

        if (!$this->isReadable()) {
            return '';
        }

        if ($this->isSeekable())
            $this->rewind();

        return $this->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {

        if (!$this->_context) {
            return;
        }

        $context = $this->detach();
        fclose($context);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {

        $context = $this->_context;
        $this->_context = null;
        $this->_data = null;

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {

        if ($this->_context === null)
            return null;

        $stat = fstat($this->_context);

        return $stat['size'];
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {

        $result = ftell($this->_context);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {

        if (!$this->_context)
            return true;

        return feof($this->_context);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {

        if (!$this->_context)
            return false;

        return $this->getMetadata('seekable') ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = \SEEK_SET)
    {

        if (!$this->isSeekable())
            throw new RuntimeException(
                "Stream is not seekable"
            );

        fseek($this->_context, $offset, $whence);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {

        return $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {

        if (!$this->_context)
            return false;

        return is_writable($this->getMetadata('uri'));
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {

        if (!$this->isWritable())
            throw new RuntimeException(
                "Stream is not writable"
            );

        return fwrite($this->_context, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {

        if (!$this->_context)
            return false;

        $mode = $this->getMetadata('mode');
        return (strstr($mode, 'r') || strstr($mode, '+'));
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {

        if (!$this->isReadable())
            throw new RuntimeException(
                "Stream is not readable"
            );

        return fread($this->_context, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {

        if (!$this->isReadable())
            throw new RuntimeException(
                "Stream is not readable"
            );

        return stream_get_contents($this->_context);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {

        if ($key === null)
            return $this->_data;

        if (!isset($this->_data[$key]))
            return null;

        return $this->_data[$key];
    }

    public static function createMemory($mode = null)
    {

        return new self(self::MEMORY, $mode);
    }

    public static function createTemp($mode = null, $maxMemory = null)
    {

        //$maxMemory is in BYTES

        $context = self::TEMP;

        if ($maxMemory)
            $context .= "/maxmemory:$maxMemory";

        return new self($context, $mode);
    }

    public static function createInput()
    {

        return new self(self::INPUT, 'rb');
    }

    public static function createOuput()
    {

        return new self(self::OUTPUT, 'wb');
    }
}