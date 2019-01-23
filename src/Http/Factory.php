<?php declare(strict_types=1);

namespace Tale\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Tale\Http\Factory\RequestFactory;
use Tale\Http\Factory\ResponseFactory;
use Tale\Http\Factory\ServerRequestFactory;
use Tale\Http\Factory\UploadedFileFactory;
use function Tale\stream_factory;
use function Tale\uri_factory;

final class Factory implements FactoryInterface
{
    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var ServerRequestFactoryInterface */
    private $serverRequestFactory;

    /** @var UploadedFileFactoryInterface */
    private $uploadedFileFactory;

    /**
     * Factory constructor.
     * @param UriFactoryInterface $uriFactory
     * @param StreamFactoryInterface $streamFactory
     * @param RequestFactoryInterface $requestFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param UploadedFileFactoryInterface $uploadedFileFactory
     */
    public function __construct(
        UriFactoryInterface $uriFactory = null,
        StreamFactoryInterface $streamFactory = null,
        RequestFactoryInterface $requestFactory = null,
        ResponseFactoryInterface $responseFactory = null,
        ServerRequestFactoryInterface $serverRequestFactory = null,
        UploadedFileFactoryInterface $uploadedFileFactory = null
    ) {
    
        $this->uriFactory = $uriFactory ?? uri_factory();
        $this->streamFactory = $streamFactory ?? stream_factory();
        $this->requestFactory = $requestFactory ?? new RequestFactory($this->uriFactory, $this->streamFactory);
        $this->responseFactory = $responseFactory ?? new ResponseFactory($this->streamFactory);
        $this->serverRequestFactory = $serverRequestFactory ?? new ServerRequestFactory(
            $this->uriFactory,
            $this->streamFactory
        );
        $this->uploadedFileFactory = $uploadedFileFactory ?? new UploadedFileFactory();
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return $this->uriFactory->createUri($uri);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return $this->streamFactory->createStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return $this->streamFactory->createStreamFromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return $this->streamFactory->createStreamFromResource($resource);
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        return $this->requestFactory->createRequest($method, $uri);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->responseFactory->createResponse($code, $reasonPhrase);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->serverRequestFactory->createServerRequest($method, $uri, $serverParams);
    }

    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
    
        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $size,
            $error,
            $clientFilename,
            $clientMediaType
        );
    }
}
