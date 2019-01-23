<?php declare(strict_types=1);

namespace Tale;

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
use Tale\Http\Factory;
use Tale\Http\Factory\RequestFactory;
use Tale\Http\Factory\ResponseFactory;
use Tale\Http\Factory\ServerRequestFactory;
use Tale\Http\Factory\UploadedFileFactory;
use Tale\Http\FactoryInterface;
use Tale\Http\Request;
use Tale\Http\Response;
use Tale\Http\ServerRequest;
use Tale\Http\UploadedFile;

function http_factory(
    UriFactoryInterface $uriFactory = null,
    StreamFactoryInterface $streamFactory = null,
    RequestFactoryInterface $requestFactory = null,
    ResponseFactoryInterface $responseFactory = null,
    ServerRequestFactoryInterface $serverRequestFactory = null,
    UploadedFileFactoryInterface $uploadedFileFactory = null
): FactoryInterface {

    return new Factory(
        $uriFactory,
        $streamFactory,
        $requestFactory,
        $responseFactory,
        $serverRequestFactory,
        $uploadedFileFactory
    );
}

function http_request(
    string $method = Request::METHOD_GET,
    $uri = '',
    array $headers = [],
    StreamInterface $body = null,
    UriFactoryInterface $uriFactory = null
): RequestInterface {
    return Request::create($method, $uri, $headers, $body, $uriFactory);
}

function http_request_factory(
    UriFactoryInterface $uriFactory = null,
    StreamFactoryInterface $streamFactory = null,
    array $headers = [],
    string $protocolVersion = Request::VERSION_1_1
): RequestFactoryInterface {
    return new RequestFactory($uriFactory, $streamFactory, $headers, $protocolVersion);
}

function http_request_create_get(
    $uri = '',
    array $headers = [],
    UriFactoryInterface $uriFactory = null
): RequestInterface {
    return Request::createGet($uri, $headers, $uriFactory);
}

function http_request_create_post(
    $uri = '',
    array $headers = [],
    StreamInterface $body = null,
    UriFactoryInterface $uriFactory = null
): RequestInterface {
    return Request::createPost($uri, $headers, $body, $uriFactory);
}

function http_request_create_put(
    $uri = '',
    array $headers = [],
    StreamInterface $body = null,
    UriFactoryInterface $uriFactory = null
): RequestInterface {
    return Request::createPut($uri, $headers, $body, $uriFactory);
}

function http_request_create_delete(
    $uri = '',
    array $headers = [],
    StreamInterface $body = null,
    UriFactoryInterface $uriFactory = null
): RequestInterface {
    return Request::createDelete($uri, $headers, $body, $uriFactory);
}

function http_request_create_options(
    $uri = '',
    array $headers = [],
    UriFactoryInterface $uriFactory = null
): RequestInterface {

    return Request::createOptions($uri, $headers, $uriFactory);
}

function http_response(
    int $statusCode = Response::STATUS_OK,
    StreamInterface $body = null,
    array $headers = []
): ResponseInterface {
    return Response::create($statusCode, $body, $headers);
}

function http_response_factory(
    StreamFactoryInterface $streamFactory = null,
    array $headers = [],
    string $protocolVersion = Request::VERSION_1_1
): ResponseFactoryInterface {

    return new ResponseFactory($streamFactory, $headers, $protocolVersion);
}

function http_response_create_ok(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createOk($body, $headers);
}

function http_response_create_moved_permanently(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createOk($body, $headers);
}

function http_response_create_temporary_redirect(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createTemporaryRedirect($body, $headers);
}

function http_response_create_permanent_redirect(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createPermanentRedirect($body, $headers);
}

function http_response_create_bad_request(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createBadRequest($body, $headers);
}

function http_response_create_unauthorized(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createUnauthorized($body, $headers);
}

function http_response_create_forbidden(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createForbidden($body, $headers);
}

function http_response_create_not_found(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createNotFound($body, $headers);
}

function http_response_create_conflict(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createConflict($body, $headers);
}

function http_response_create_unprocessable_entity(StreamInterface $body = null, array $headers = []): ResponseInterface
{
    return Response::createUnprocessableEntity($body, $headers);
}

function http_response_create_internal_server_error(
    StreamInterface $body = null,
    array $headers = []
): ResponseInterface {
    return Response::createInternalServerError($body, $headers);
}

function http_server_request(
    string $method = ServerRequest::METHOD_GET,
    UriInterface $uri = null,
    StreamInterface $body = null,
    array $headers = [],
    array $serverParams = [],
    array $queryParams = [],
    array $cookieParams = [],
    array $uploadedFiles = [],
    array $attributes = []
): ServerRequestInterface {
    return ServerRequest::create(
        $method,
        $uri,
        $body,
        $headers,
        $serverParams,
        $queryParams,
        $cookieParams,
        $uploadedFiles,
        $attributes
    );
}

function http_server_request_factory(
    UriFactoryInterface $uriFactory = null,
    StreamFactoryInterface $streamFactory = null,
    array $headers = [],
    string $protocolVersion = ServerRequest::VERSION_1_1
): ServerRequestFactoryInterface {
    return new ServerRequestFactory($uriFactory, $streamFactory, $headers, $protocolVersion);
}

function http_uploaded_file(
    StreamInterface $stream = null,
    ?int $size = null,
    int $error = UploadedFile::ERROR_OK,
    ?string $clientFilename = null,
    ?string $clientMediaType = null
): UploadedFileInterface {
    return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
}

function http_uploaded_file_factory(): UploadedFileFactoryInterface
{
    return new UploadedFileFactory();
}
