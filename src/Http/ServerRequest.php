<?php
declare(strict_types=1);

namespace Tale\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest extends AbstractRequest implements ServerRequestInterface
{
    /**
     * @var array
     */
    private $serverParams;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * @var array
     */
    private $cookieParams;

    /**
     * @var array
     */
    private $uploadedFiles;

    /**
     * @var array
     */
    private $parsedBody;

    /**
     * @var array
     */
    private $attributes;

    public function __construct(
        string $method = self::METHOD_GET,
        UriInterface $uri = null,
        StreamInterface $body = null,
        array $headers = [],
        array $serverParams = [],
        array $queryParams = [],
        array $cookieParams = [],
        array $uploadedFiles = [],
        $parsedBody = null,
        array $attributes = [],
        string $requestTarget = '',
        string $protocolVersion = self::VERSION_1_1
    ) {
    
        parent::__construct($method, $uri, $body, $headers, $requestTarget, $protocolVersion);
        $this->serverParams = $serverParams;
        $this->queryParams = $queryParams;
        $this->cookieParams = $cookieParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->parsedBody = $this->filterParsedBody($parsedBody);
        $this->attributes = $attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withQueryParams(array $query): self
    {
        $request = clone $this;
        $request->queryParams = $query;
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withCookieParams(array $cookies): self
    {
        $request = clone $this;
        $request->cookieParams = $cookies;
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $request = clone $this;
        $request->uploadedFiles = $uploadedFiles;
    }

    /**
     * {@inheritDoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withParsedBody($parsedBody): self
    {
        $request = clone $this;
        $request->parsedBody = $this->filterParsedBody($parsedBody);
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withAttribute($name, $value): self
    {
        $request = clone $this;
        $request->attributes[$name] = $value;
        return $request;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withoutAttribute($name): self
    {
        $request = clone $this;
        unset($request->attributes[$name]);
        return $request;
    }

    private function filterParsedBody($parsedBody)
    {
        if ($parsedBody !== null && !\is_array($parsedBody) && !\is_object($parsedBody)) {
            throw new \InvalidArgumentException('Structured data should either be null, an object or an array');
        }
        return $parsedBody;
    }

    public static function create(
        string $method = self::METHOD_GET,
        UriInterface $uri = null,
        StreamInterface $body = null,
        array $headers = [],
        array $serverParams = [],
        array $queryParams = [],
        array $cookieParams = [],
        array $uploadedFiles = [],
        array $attributes = []
    ): self {
        return new self(
            $method,
            $uri,
            $body,
            $headers,
            $serverParams,
            $queryParams,
            $cookieParams,
            $uploadedFiles,
            null,
            $attributes
        );
    }
}
