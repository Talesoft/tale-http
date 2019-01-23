<?php declare(strict_types=1);

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Fig\Http\Message\RequestMethodInterface;
use function Tale\uri_parse;

abstract class AbstractRequest extends AbstractMessage implements RequestInterface, RequestMethodInterface
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var string
     */
    private $requestTarget;

    public function __construct(
        string $method = self::METHOD_GET,
        UriInterface $uri = null,
        StreamInterface $body = null,
        array $headers = [],
        string $requestTarget = '',
        string $protocolVersion = self::VERSION_1_1
    ) {
    
        parent::__construct($this->buildRequestHeaders($uri, $headers), $body, $protocolVersion);
        $this->method = $this->filterMethod($method);
        $this->uri = $uri ?? uri_parse('/');
        $this->requestTarget = $requestTarget;
    }

    /**
     * {@inheritDoc}
     */
    final public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            return '/';
        }

        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= "?$query";
        }

        $fragment = $this->uri->getFragment();
        if ($fragment !== '') {
            $target .= "#$fragment";
        }
        return $target;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withRequestTarget($requestTarget): self
    {
        $request = clone $this;
        $request->requestTarget = (string)$requestTarget;
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    final public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withMethod($method): self
    {
        $request = clone $this;
        $request->method = $this->filterMethod($method);

        return $request;
    }

    /**
     * {@inheritDoc}
     */
    final public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $request = clone $this;
        $request->uri = $uri;

        $uriHost = $uri->getHost();
        if ($preserveHost || $uriHost === '') {
            return $request;
        }

        $uriPort = $uri->getPort();
        if ($uriPort !== null) {
            $uriHost .= ":{$uriPort}";
        }
        return $request->withHeader('Host', $uriHost);
    }

    private function buildRequestHeaders(?UriInterface $uri, array $headers): array
    {
        if ($uri === null) {
            return $headers;
        }

        $host = $uri->getHost();
        if ($host === '') {
            return $headers;
        }

        $port = $uri->getPort();
        if ($port !== null) {
            $host .= ":{$port}";
        }

        if (!isset($headers['host']) && !isset($headers['Host'])) {
            $headers['Host'] = $host;
        }
        return $headers;
    }

    private function filterMethod($method): string
    {
        if (!\is_string($method)) {
            throw new InvalidArgumentException('Passed HTTP method needs to be a string');
        }
        return $method;
    }
}
