<?php declare(strict_types=1);

namespace Tale\Http\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tale\Http\Response;

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
    public function __construct(string $protocolVersion, array $headers, StreamFactoryInterface $streamFactory)
    {
        $this->protocolVersion = $protocolVersion;
        $this->headers = $headers;
        $this->streamFactory = $streamFactory;
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(
            $this->protocolVersion,
            $this->headers,
            $code,
            $reasonPhrase ?: null, //For some reason, PSR-7 defaults reasonPhrase to null, but PSR-17 defaults it to ''
            $this->streamFactory->createStream()
        );
    }
}