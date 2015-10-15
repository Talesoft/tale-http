<?php

namespace Tale\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class MessageBase implements MessageInterface
{

    const DEFAULT_VERSION = '1.1';

    private $_protocolVersion;
    private $_headers;
    private $_headerNames;
    private $_body;

    public function __construct(StreamInterface $body)
    {

        $this->_protocolVersion = self::DEFAULT_VERSION;
        $this->_headers = [];
        $this->_headerNames = [];
        $this->_body = $body;
    }

    public function getProtocol()
    {

        return 'HTTP/'.$this->getProtocolVersion();
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {

        return $this->_protocolVersion;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {

        $message = clone $this;
        $message->_protocolVersion = $version;

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {

        return $this->_headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {

        return isset($this->_headerNames[strtolower($name)]);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {

        if (!$this->hasHeader($name))
            return [];

        $name = $this->_headerNames[strtolower($name)];
        return $this->_headers[$name];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {

        if (!$this->hasHeader($name))
            return null;

        return implode(',', $this->getHeader($name));
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {

        if (!is_array($value))
            $value = [$value];

        $value = array_map('strval', $value);
        $lowerName = strtolower($name);

        $message = clone $this;
        $message->_headerNames[$lowerName] = $name;
        $message->_headers[$name] = $value;

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {

        return $this->withHeader($name, array_merge(
            $this->getHeader($name),
            is_array($value) ? $value : [$value]
        ));
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {

        if (!$this->hasHeader($name))
            return clone $this;

        $lowerName = strtolower($name);
        $name = $this->_headerNames[$lowerName];

        $message = clone $this;
        unset($message->_headers[$name], $message->_headerNames[$lowerName]);

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {

        return $this->_body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {

        $message = clone $this;
        $message->_body = $body;

        return $message;
    }

    public function __toString()
    {

        $crlf = "\r\n";
        $headers = [];
        foreach ($this->_headerNames as $name) {

            $headers[] = "$name: ".$this->getHeaderLine($name).$crlf;
        }

        $body = $this->_body->getContents();

        return implode('', $headers).$crlf.$body;
    }
}