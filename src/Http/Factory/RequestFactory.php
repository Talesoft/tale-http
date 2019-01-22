<?php declare(strict_types=1);

namespace Tale\Http\Factory;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Tale\Http\Request;

final class RequestFactory implements RequestFactoryInterface
{
    /** @var string */
    private $protocolVersion;

    /** @var array */
    private $headers;

    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /**
     * RequestFactory constructor.
     * @param string $protocolVersion
     * @param array $headers
     * @param UriFactoryInterface $uriFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        string $protocolVersion,
        array $headers,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory
    )
    {
        $this->protocolVersion = $protocolVersion;
        $this->headers = $headers;
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        $uri = $uri instanceof UriInterface ? $uri : $this->uriFactory->createUri($uri);
        return new Request(
            $this->protocolVersion,
            $this->headers,
            $method,
            $uri,
            null,
            $this->streamFactory->createStream()
        );
    }
}