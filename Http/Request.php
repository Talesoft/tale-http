<?php

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends MessageBase implements RequestInterface
{

    private $method;
    private $uri;
    private $requestTarget;

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
        $uri = $this->filterUri($uri);

        if (!isset($headers['Host']) && $uri->getHost())
            $headers['Host'] = $uri->getHost();

        parent::__construct($body, $headers, $protocolVersion);


        $this->uri = $uri;
        $this->method = $method !== null
                       ? $this->filterMethod($method)
                       : Method::GET;

        $this->requestTarget = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestTarget()
    {

        if (!empty($this->requestTarget))
            return $this->requestTarget;

        $target = $this->uri->getPath();

        if (empty($target))
            return '/';

        $query = $this->uri->getQuery();
        if (!empty($query))
            $target .= "?$query";

        $fragment = $this->uri->getFragment();
        if (!empty($fragment))
            $target .= "#$fragment";

        return $target;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withRequestTarget($requestTarget)
    {

        $request = clone $this;
        $request->requestTarget = !empty($requestTarget)
                                 ? strval($requestTarget)
                                 : null;

        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod()
    {

        return $this->method;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withMethod($method)
    {

        $request = clone $this;
        $request->method = $this->filterMethod($method);

        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getUri()
    {

        return $this->uri;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {

        $request = clone $this;
        $request->uri = $this->filterUri($uri);

        $uriHost = $uri->getHost();
        if ($preserveHost || empty($uriHost))
            return $request;

        $uriPort = $uri->getPort();
        if (!empty($uriPort))
            $uriHost .= ":$uriPort";

        return $request->withHeader('Host', $uriHost);
    }

    private function filterMethod($method)
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

    private function filterUri($uri)
    {

        return $uri instanceof Uri ? $uri : new Uri($uri);
    }
}