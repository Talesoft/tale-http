<?php declare(strict_types=1);

namespace Tale\Http\Factory;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Tale\Http\Request;
use function Tale\stream_factory;
use function Tale\uri_factory;

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
        UriFactoryInterface $uriFactory = null,
        StreamFactoryInterface $streamFactory = null,
        array $headers = [],
        string $protocolVersion = Request::VERSION_1_1
    ) {
    
        $this->uriFactory = $uriFactory ?? uri_factory();
        $this->streamFactory = $streamFactory ?? stream_factory();
        $this->headers = $headers;
        $this->protocolVersion = $protocolVersion;
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        $uri = $uri instanceof UriInterface ? $uri : $this->uriFactory->createUri($uri);
        $body = $this->streamFactory->createStream();
        return new Request($method, $uri, $body, $this->headers, '', $this->protocolVersion);
    }
}
