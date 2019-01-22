<?php declare(strict_types=1);

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response extends AbstractMessage implements ResponseInterface
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
        string $protocolVersion,
        array $headers,
        int $statusCode,
        ?string $reasonPhrase,
        StreamInterface $body
    )
    {
        parent::__construct($protocolVersion, $headers, $body);

        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
    }

    /**
     * {@inheritDoc}
     */
    final public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withStatus($code, $reasonPhrase = null): self
    {
        if ($reasonPhrase !== null && !\is_string($reasonPhrase)) {
            throw new \InvalidArgumentException('Argument 2 passed to Response->withStatus should be string or null');
        }

        $response = clone $this;
        $response->statusCode = $this->filterStatusCode($code);
        $response->reasonPhrase = $reasonPhrase ?? StatusCode::getDefaultReasonPhrase($code);

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    final public function getReasonPhrase(): string
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
}