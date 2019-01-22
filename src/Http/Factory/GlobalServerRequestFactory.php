<?php declare(strict_types=1);

namespace Tale\Http\Factory;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Tale\Http\Method;
use Tale\Http\ServerRequest;

final class GlobalServerRequestFactory implements GlobalServerRequestFactoryInterface
{
    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    /** @var ServerRequestFactoryInterface */
    private $serverRequestFactory;

    /** @var UploadedFileFactoryInterface */
    private $uploadedFileFactory;

    /**
     * GlobalServerRequestFactory constructor.
     * @param UriFactoryInterface $uriFactory
     * @param StreamFactoryInterface $streamFactory
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param UploadedFileFactoryInterface $uploadedFileFactory
     */
    public function __construct(
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory,
        ServerRequestFactoryInterface $serverRequestFactory,
        UploadedFileFactoryInterface $uploadedFileFactory
    )
    {
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
        $this->serverRequestFactory = $serverRequestFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
    }

    /**
     * @return ServerRequest
     */
    public function createGlobalServerRequest(): ServerRequestInterface
    {
        $request = $this->serverRequestFactory
            ->createServerRequest($this->getRequestMethod(), $this->getRequestUri(), $_SERVER)
            ->withProtocolVersion($this->getRequestProtocolVersion())
            ->withBody($this->getRequestBody())
            ->withQueryParams($this->getRequestQueryParams())
            ->withCookieParams($this->getRequestCookieParams())
            ->withUploadedFiles($this->getRequestUploadedFiles())
            ->withParsedBody($this->getRequestParsedBody());

        $headers = $this->getRequestHeaders();
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }
        return $request;
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return string|int
     */
    private function getServerParam(string $name, $default = null)
    {
        return $_SERVER[$name] ?? $default;
    }

    /**
     * @return UriInterface
     */
    private function getRequestUri(): UriInterface
    {
        /** @var UriInterface $uri */
        $uri = $this->uriFactory->createUri();
        $scheme = 'http';
        $https = $this->getServerParam('HTTPS');
        if ($https && $https !== 'off') {
            $scheme = 'https';
        }
        $uri = $uri->withScheme($scheme);

        $host = $this->getServerParam('HTTP_HOST', $this->getServerParam('SERVER_NAME'));
        if (!empty($host)) {
            $uri = $uri->withHost($host);
        }

        $port = $this->getServerParam('SERVER_PORT');
        if (!empty($port)) {
            $uri = $uri->withPort($port);
        }

        $path = $this->getServerParam('PATH_INFO');
        if (empty($path)) {
            $path = $this->getServerParam('REDIRECT_REQUEST_URI', $this->getServerParam('REQUEST_URI'));
        }

        if (empty($path)) {
            $path = '/';
        }

        $pos = null;
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        $uri = $uri->withPath($path);
        $query = $this->getServerParam('REDIRECT_QUERY_STRING', $this->getServerParam('QUERY_STRING'));
        if (!empty($query)) {
            $uri = $uri->withQuery($query);
        }

        return $uri;
    }

    /**
     * @return string
     */
    private function getRequestMethod(): string
    {
        return $this->getServerParam('REQUEST_METHOD', Method::GET);
    }

    /**
     * @return StreamInterface
     */
    private function getRequestBody(): StreamInterface
    {
        return $this->streamFactory->createStreamFromFile('php://input', 'rb');
    }

    /**
     * @return string[]
     */
    private function getRequestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strncmp($name, 'HTTP_', 5) === 0) {
                $name = implode('-', array_map('ucfirst', explode('_', strtolower(substr($name, 5)))));
                $headers[$name] = $value;
                continue;
            }

            if (strncmp($name, 'CONTENT_', 8) === 0) {
                $name = implode('-', array_map('ucfirst', explode('_', strtolower($name))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * @return string
     */
    private function getRequestProtocolVersion(): string
    {
        [, $version] = explode('/', $this->getServerParam('SERVER_PROTOCOL', 'HTTP/1.1'));
        return $version;
    }

    /**
     * @return string[]
     */
    private function getRequestQueryParams(): array
    {
        return $_GET ?? [];
    }

    /**
     * @return string[]
     */
    private function getRequestCookieParams(): array
    {
        return $_COOKIE ?? [];
    }

    /**
     * @return UploadedFileInterface[]
     */
    private function getRequestUploadedFiles(): array
    {
        if ($_FILES === null) {
            return [];
        }
        return $this->filterUploadedFiles($_FILES);
    }

    /**
     * @param array $files
     *
     * @return UploadedFileInterface[]
     */
    private function filterUploadedFiles(array $files): array
    {
        $result = [];
        foreach ($files as $key => $fileInfo) {
            if ($fileInfo instanceof UploadedFileInterface) {
                $result[$key] = $fileInfo;
                continue;
            }
            if (\is_array($fileInfo) && isset($fileInfo['tmp_name'])) {
                $result[$key] = $this->filterUploadedFile($fileInfo);
                continue;
            }
            if (\is_array($fileInfo)) {
                $result[$key] = $this->filterUploadedFiles($fileInfo);
            }
        }
        return $result;
    }

    /**
     * @param array $fileInfo
     *
     * @return UploadedFileInterface|UploadedFileInterface[]
     */
    private function filterUploadedFile(array $fileInfo)
    {
        if (\is_array($fileInfo['tmp_name'])) {
            return $this->filterNestedUploadedFiles($fileInfo);
        }

        $stream = $this->streamFactory->createStreamFromFile($fileInfo['tmp_name'], 'rb');
        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $fileInfo['size'],
            $fileInfo['error'],
            $fileInfo['name'],
            $fileInfo['type']
        );
    }

    /**
     * @param array $files
     *
     * @return UploadedFileInterface[]
     */
    private function filterNestedUploadedFiles(array $files): array
    {
        $result = [];
        foreach (array_keys($files['tmp_name']) as $key) {
            $fileInfo = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key]
            ];
            $result[$key] = $this->filterUploadedFile($fileInfo);
        }
        return $result;
    }

    /**
     * @return mixed|\SimpleXMLElement
     */
    private function getRequestParsedBody()
    {
        $headers = $this->getRequestHeaders();
        $contentType = $headers['Content-Type'] ?? ($headers['content-type'] ?? null);
        if ($this->getRequestMethod() === Method::POST
            && \in_array($contentType, ['multipart/form-data', 'application/x-www-form-urlencoded'])) {
            return $_POST;
        }

        $body = $this->getRequestBody();
        if (!$body->isReadable() || $body->eof()) {
            return null;
        }

        switch(strtolower($contentType)) {
            case 'application/json':
                return json_decode((string)$body);
                break;
            case 'text/xml':
                return simplexml_load_string((string)$body);
                break;
        }
        parse_str((string)$body, $data);
        //Try to rewind after parsing
        if ($body->isSeekable()) {
            $body->rewind();
        }
        return $data;
    }
}