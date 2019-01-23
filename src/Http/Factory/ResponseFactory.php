<?php declare(strict_types=1);

namespace Tale\Http\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tale\Http\Response;
use function Tale\stream_factory;

final class ResponseFactory implements ResponseFactoryInterface
{
    /** @var string */
    private $protocolVersion;

    /** @var array */
    private $headers;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /**
     * RequestFactory constructor.
     * @param string $protocolVersion
     * @param array $headers
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        StreamFactoryInterface $streamFactory = null,
        array $headers = [],
        string $protocolVersion = Response::VERSION_1_1
    ) {
    
        $this->streamFactory = $streamFactory ?? stream_factory();
        $this->headers = $headers;
        $this->protocolVersion = $protocolVersion;
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $body = $this->streamFactory->createStream();
        return new Response($code, $body, $this->headers, $reasonPhrase, $this->protocolVersion);
    }
}
