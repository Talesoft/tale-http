<?php declare(strict_types=1);

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use function Tale\stream_create_memory;

abstract class AbstractMessage implements MessageInterface
{
    public const VERSION_1_0 = '1.0';
    public const VERSION_1_1 = '1.1';
    public const VERSION_2_0 = '2.0';

    /**
     * @var string
     */
    private $protocolVersion;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $headerNames = [];

    /**
     * @var StreamInterface
     */
    private $body;

    public function __construct(
        array $headers = [],
        StreamInterface $body = null,
        string $protocolVersion = self::VERSION_1_1
    ) {
    
        $this->protocolVersion = $protocolVersion;
        $this->addHeaders($headers);
        $this->body = $body ?? stream_create_memory();
    }

    /**
     * {@inheritDoc}
     */
    final public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withProtocolVersion($version): self
    {
        $message = clone $this;
        $message->protocolVersion = $version;

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    final public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritDoc}
     */
    final public function hasHeader($name): bool
    {
        return array_key_exists(strtolower($name), $this->headerNames);
    }

    /**
     * {@inheritDoc}
     */
    final public function getHeader($name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $name = $this->headerNames[strtolower($name)];
        return $this->headers[$name];
    }

    /**
     * {@inheritDoc}
     */
    final public function getHeaderLine($name): string
    {
        if (!$this->hasHeader($name)) {
            return '';
        }

        return implode(',', $this->getHeader($name));
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withHeader($name, $value): self
    {
        $message = clone $this;

        //Make sure to remove the current header with the same
        //name to avoid casing-duplication (location, Location, LOCATION)
        if ($message->hasHeader($name)) {
            $message = $message->withoutHeader($name);
        }

        $message->addHeaders([$name => $value]);
        return $message;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withAddedHeader($name, $value): self
    {
        return $this->withHeader($name, array_merge($this->getHeader($name), (array)$value));
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withoutHeader($name): self
    {
        if (!$this->hasHeader($name)) {
            return clone $this;
        }

        $lowerName = strtolower($name);
        $name = $this->headerNames[$lowerName];

        $message = clone $this;
        unset($message->headers[$name], $message->headerNames[$lowerName]);
        return $message;
    }

    /**
     * {@inheritDoc}
     */
    final public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    final public function withBody(StreamInterface $body): self
    {
        $message = clone $this;
        $message->body = $body;

        return $message;
    }

    private function filterHeaderName(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
    }

    private function filterHeaderValue($value): array
    {
        if (!\is_array($value)) {
            $value = [$this->filterHeaderString($value)];
        }

        foreach ($value as $i => $val) {
            $value[$i] = $this->filterHeaderString($val);
        }

        return $value;
    }

    private function filterHeaderString($string): string
    {
        if (!\is_string($string)) {
            throw new InvalidArgumentException('The header string can only consist of string values');
        }

        if (strpos($string, "\r") !== false || strpos($string, "\n") !== false) {
            throw new InvalidArgumentException('Header string should never contain CR or LF characters');
        }
        return str_replace("\0", '', $string);
    }

    private function addHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $name = $this->filterHeaderName($this->filterHeaderString($name));
            $value = $this->filterHeaderValue($value);

            $lowerName = strtolower($name);
            $this->headers[$name] = $value;
            $this->headerNames[$lowerName] = $name;
        }
    }
}
