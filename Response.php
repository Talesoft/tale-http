<?php

namespace Tale\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{

    private $_statusCode;
    private $_reasonPhrase;

    public function __construct($statusCode = null, $reasonPhrase = null, StreamInterface $body = null)
    {


    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {
        // TODO: Implement getStatusCode() method.
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        // TODO: Implement withStatus() method.
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {
        // TODO: Implement getReasonPhrase() method.
    }


}