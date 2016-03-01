<?php

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Tale\Stream\MemoryStream;

abstract class MessageBase implements MessageInterface
{

    const DEFAULT_VERSION = '1.1';

    private $protocolVersion;
    private $headers;
    private $headerNames;
    private $body;

    public function __construct(
        StreamInterface $body = null,
        array $headers = null,
        $protocolVersion = null
    )
    {

        $this->protocolVersion = $protocolVersion
                                ? $protocolVersion
                                : self::DEFAULT_VERSION;
        $this->headers = [];
        $this->headerNames = [];
        $this->body = $body ? $body : new MemoryStream();

        if ($headers)
            $this->addHeaders($headers);
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocolVersion()
    {

        return $this->protocolVersion;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withProtocolVersion($version)
    {

        $message = clone $this;
        $message->protocolVersion = $version;

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders()
    {

        return $this->headers;
    }

    /**
     * {@inheritDoc}
     */
    public function hasHeader($name)
    {

        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeader($name)
    {

        if (!$this->hasHeader($name))
            return [];

        $name = $this->headerNames[strtolower($name)];
        return $this->headers[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderLine($name)
    {

        if (!$this->hasHeader($name))
            return '';

        return implode(',', $this->getHeader($name));
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withHeader($name, $value)
    {

        $message = clone $this;

        //Make sure to remove the current header with the same
        //name to avoid casing-duplication (location, Location, LOCATION)
        if ($message->hasHeader($name))
            $message = $message->withoutHeader($name);

        $message->addHeaders([$name => $value]);

        return $message;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withAddedHeader($name, $value)
    {

        return $this->withHeader($name, array_merge(
            $this->getHeader($name),
            is_array($value) ? $value : [$value]
        ));
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withoutHeader($name)
    {

        if (!$this->hasHeader($name))
            return clone $this;

        $lowerName = strtolower($name);
        $name = $this->headerNames[$lowerName];

        $message = clone $this;
        unset($message->headers[$name], $message->headerNames[$lowerName]);

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody()
    {

        return $this->body;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withBody(StreamInterface $body)
    {

        $message = clone $this;
        $message->body = $body;

        return $message;
    }

    private function filterHeaderName($value)
    {

        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
    }

    private function filterHeaderValue($value)
    {

        if (!is_array($value))
            $value = [$value];

        foreach ($value as $i => $val) {

            if (!is_string($val))
                throw new InvalidArgumentException(
                    "The header value can only consist of string values"
                );

            if (strpos($val, "\r") !== false || strpos($val, "\n") !== false)
                throw new InvalidArgumentException(
                    "Header values should never contain CR or LF characters"
                );

            $value[$i] = str_replace("\0", '', $val);
        }

        return $value;
    }

    private function addHeaders(array $headers)
    {

        foreach ($headers as $name => $value) {

            if (!is_string($name))
                throw new InvalidArgumentException(
                    "The passed header name is not a string"
                );

            if (strpos($name, "\r") !== false || strpos($name, "\n") !== false)
                throw new InvalidArgumentException(
                    "Header names should never contain CR or LF characters"
                );

            $name = $this->filterHeaderName($name);
            $value = $this->filterHeaderValue($value);

            $lowerName = strtolower($name);
            $this->headers[$name] = $value;
            $this->headerNames[$lowerName] = $name;
        }
    }
}