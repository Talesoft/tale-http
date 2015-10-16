<?php

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends MessageBase implements RequestInterface
{

    const DEFAULT_METHOD = Method::GET;

    private $_method;
    private $_uri;
    private $_requestTarget;

    public function __construct(
        $uri = null,
        $method = null,
        StreamInterface $body = null,
        array $headers = null,
        $protocolVersion = null
    )
    {

        //Make sure to handle the host header and pass it before
        //we initialize the message-base
        $uri = $this->_filterUri($uri);

        if (!isset($headers['Host']) && $uri->getHost())
            $headers['Host'] = $uri->getHost();

        parent::__construct($body, $headers, $protocolVersion);


        $this->_uri = $uri;
        $this->_method = $method !== null
                       ? $this->_filterMethod($method)
                       : self::DEFAULT_METHOD;

        $this->_requestTarget = null;
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {

        if (!empty($this->_requestTarget))
            return $this->_requestTarget;

        $target = $this->_uri->getPath();

        if (empty($target))
            return '/';

        $query = $this->_uri->getQuery();
        if (!empty($query))
            $target .= "?$query";

        $fragment = $this->_uri->getFragment();
        if (!empty($fragment))
            $target .= "#$fragment";

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {

        $request = clone $this;
        $request->_requestTarget = !empty($requestTarget)
                                 ? strval($requestTarget)
                                 : null;

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {

        return $this->_method;
    }

    public function isMethod($method)
    {

        return $this->_method === $method;
    }

    public function isGet()
    {

        return $this->isMethod(Method::GET);
    }

    public function isPost()
    {

        return $this->isMethod(Method::POST);
    }

    public function isPut()
    {

        return $this->isMethod(Method::PUT);
    }

    public function isPostOrPut()
    {

        return $this->isPost() || $this->isPut();
    }

    public function isDelete()
    {

        return $this->isMethod(Method::DELETE);
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {

        $request = clone $this;
        $request->_method = $this->_filterMethod($method);

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {

        return $this->_uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {

        $request = clone $this;
        $request->_uri = $this->_filterUri($uri);

        $uriHost = $uri->getHost();
        if ($preserveHost || empty($uriHost))
            return $request;

        $uriPort = $uri->getPort();
        if (!empty($uriPort))
            $uriHost .= ":$uriPort";

        return $request->withHeader('Host', $uriHost);
    }

    /**
     *
     * @todo Maybe automatically translate some request headers to fitting response headers?
     * @param null       $statusCode
     * @param array|null $headers
     * @param null       $reasonPhrase
     * @param null       $protocolVersion
     *
     * @return Response
     */
    public function createResponse(
        $statusCode = null,
        array $headers = null,
        $reasonPhrase = null,
        $protocolVersion = null
    )
    {

        return new Response(
            Stream::createTempStream(),
            $statusCode,
            $headers,
            $reasonPhrase,
            $protocolVersion ? $protocolVersion : $this->getProtocolVersion()
        );
    }

    private function _filterMethod($method)
    {

        if (!is_string($method))
            throw new InvalidArgumentException(
                "Passed HTTP method needs to be a string"
            );

        $method = strtoupper($method);
        if (!defined(Method::class."::$method"))
            throw new InvalidArgumentException(
                "The passed method is not a valid HTTP method"
            );

        return constant(Method::class."::$method");
    }

    private function _filterUri($uri)
    {

        if ($uri === null)
            return new Uri();

        return $uri instanceof Uri ? $uri : new Uri($uri);
    }

    protected function getInitialHeaderLine()
    {

        return implode(' ', [
            $this->getMethod(),
            $this->getRequestTarget(),
            $this->getProtocol()
        ]);
    }
}