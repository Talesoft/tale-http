<?php declare(strict_types=1);

namespace Tale\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use function Tale\stream_null;
use function Tale\uri_factory;

final class Request extends AbstractRequest
{
    public static function create(
        string $method = self::METHOD_GET,
        $uri = '',
        array $headers = [],
        StreamInterface $body = null,
        UriFactoryInterface $uriFactory = null
    ): self {
    
        if (!($uri instanceof UriInterface) && !is_string($uri)) {
            throw new \InvalidArgumentException('Passed uri either needs to be UriInterface instance or string');
        }
        $uriFactory = $uriFactory ?? uri_factory();
        return new self($method, $uri instanceof UriInterface ? $uri : $uriFactory->createUri($uri), $body, $headers);
    }

    public static function createGet($uri = null, array $headers = [], UriFactoryInterface $uriFactory = null): self
    {
        return self::create(self::METHOD_GET, $uri, $headers, stream_null(), $uriFactory);
    }

    public static function createPost(
        $uri = '',
        array $headers = [],
        StreamInterface $body = null,
        UriFactoryInterface $uriFactory = null
    ): self {
    
        return self::create(self::METHOD_POST, $uri, $headers, $body, $uriFactory);
    }

    public static function createPut(
        $uri = '',
        array $headers = [],
        StreamInterface $body = null,
        UriFactoryInterface $uriFactory = null
    ): self {
    
        return self::create(self::METHOD_PUT, $uri, $headers, $body, $uriFactory);
    }

    public static function createDelete(
        $uri = '',
        array $headers = [],
        StreamInterface $body = null,
        UriFactoryInterface $uriFactory = null
    ): self {
    
        return self::create(self::METHOD_DELETE, $uri, $headers, $body, $uriFactory);
    }

    public static function createOptions($uri = '', array $headers = [], UriFactoryInterface $uriFactory = null): self
    {
        return self::create(self::METHOD_OPTIONS, $uri, $headers, stream_null(), $uriFactory);
    }
}
