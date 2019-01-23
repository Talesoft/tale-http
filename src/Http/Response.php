<?php declare(strict_types=1);

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response extends AbstractMessage implements ResponseInterface, StatusCodeInterface
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $reasonPhrase;

    public function __construct(
        int $statusCode = self::STATUS_OK,
        StreamInterface $body = null,
        array $headers = [],
        string $reasonPhrase = '',
        string $protocolVersion = self::VERSION_1_1
    ) {
    
        parent::__construct($headers, $body, $protocolVersion);
        $this->statusCode = $this->filterStatusCode($statusCode);
        $this->reasonPhrase = $this->filterReasonPhrase($reasonPhrase);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function withStatus($code, $reasonPhrase = null): self
    {
        if ($reasonPhrase !== null && !\is_string($reasonPhrase)) {
            throw new \InvalidArgumentException('Argument 2 passed to Response->withStatus should be string or null');
        }
        $response = clone $this;
        $response->statusCode = $response->filterStatusCode($code);
        $response->reasonPhrase = $response->filterReasonPhrase($reasonPhrase);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function filterStatusCode($code): int
    {
        if (!\is_int($code)) {
            throw new InvalidArgumentException('StatusCode needs to be an integer');
        }

        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(
                'StatusCode needs to be a valid HTTP status code.' .
                ' It\'s usually a number between 100 and 600'
            );
        }
        return $code;
    }

    private function filterReasonPhrase($reasonPhrase): string
    {
        if ($reasonPhrase !== null && !is_string($reasonPhrase)) {
            throw new InvalidArgumentException('StatusCode needs to be a string or null');
        }

        return $reasonPhrase === '' || $reasonPhrase === null
            ? (self::REASON_PHRASES[$this->statusCode] ?? '')
            : $reasonPhrase;
    }

    public static function create(
        int $statusCode = self::STATUS_OK,
        StreamInterface $body = null,
        array $headers = []
    ): self {
    
        return new self($statusCode, $body, $headers);
    }

    public static function createOk(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_OK, $body, $headers);
    }

    public static function createMovedPermanently(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_MOVED_PERMANENTLY, $body, $headers);
    }

    public static function createTemporaryRedirect(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_TEMPORARY_REDIRECT, $body, $headers);
    }

    public static function createPermanentRedirect(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_PERMANENT_REDIRECT, $body, $headers);
    }

    public static function createBadRequest(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_BAD_REQUEST, $body, $headers);
    }

    public static function createUnauthorized(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_UNAUTHORIZED, $body, $headers);
    }

    public static function createForbidden(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_FORBIDDEN, $body, $headers);
    }

    public static function createNotFound(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_NOT_FOUND, $body, $headers);
    }

    public static function createConflict(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_CONFLICT, $body, $headers);
    }

    public static function createUnprocessableEntity(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_UNPROCESSABLE_ENTITY, $body, $headers);
    }

    public static function createInternalServerError(StreamInterface $body = null, array $headers = []): self
    {
        return self::create(self::STATUS_INTERNAL_SERVER_ERROR, $body, $headers);
    }
}
