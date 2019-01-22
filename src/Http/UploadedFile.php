<?php declare(strict_types=1);

namespace Tale\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

final class UploadedFile implements UploadedFileInterface
{
    public const ERROR_OK = \UPLOAD_ERR_OK;
    public const ERROR_INI_SIZE = \UPLOAD_ERR_INI_SIZE;
    public const ERROR_FORM_SIZE = \UPLOAD_ERR_FORM_SIZE;
    public const ERROR_PARTIAL = \UPLOAD_ERR_PARTIAL;
    public const ERROR_NO_FILE = \UPLOAD_ERR_NO_FILE;
    public const ERROR_NO_TMP_DIR = \UPLOAD_ERR_NO_TMP_DIR;
    public const ERROR_CANT_WRITE = \UPLOAD_ERR_CANT_WRITE;
    public const ERROR_EXTENSION = \UPLOAD_ERR_EXTENSION;

    /** @var StreamInterface|null  */
    private $stream;
    /** @var int|null  */
    private $size;
    /** @var int  */
    private $error;
    /** @var string|null  */
    private $clientFilename;
    /** @var string|null  */
    private $clientMediaType;

    public function __construct(
        StreamInterface $stream,
        ?int $size,
        int $error,
        ?string $clientFilename,
        ?string $clientMediaType
    )
    {
        $this->stream = $stream;
        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * {@inheritDoc}
     */
    public function getStream(): StreamInterface
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Uploaded file has already been moved');
        }
        return $this->stream;
    }

    /**
     * {@inheritDoc}
     */
    public function moveTo($targetPath): void
    {
        if (!is_writable($targetPath)) {
            throw new \RuntimeException("Upload move error: Target path {$targetPath} is not writable");
        }

        if ($this->stream === null) {
            throw new \RuntimeException('Upload move error: Uploaded file has already been moved');
        }

        $uri = $this->stream->getMetadata('uri');
        if ($uri && \is_readable($uri)) {
            //We have a direct source path we can work on (and possibly clean up)
            //Close the stream down
            $this->stream->close();
            if (empty(PHP_SAPI) || strncmp('cli', PHP_SAPI, 3) === 0) {
                if (!rename($uri, $targetPath)) {
                    throw new \RuntimeException("Upload move error: Failed to rename {$uri} to {$targetPath}");
                }
            } else if (!move_uploaded_file($uri, $targetPath)) {
                throw new \RuntimeException("Upload move error: Failed to move {$uri} to {$targetPath}");
            }

            if (!unlink($uri)) {
                throw new \RuntimeException("Upload move error: Failed to remove {$uri}");
            }
            return;
        }

        //There is no URI associated with this stream or it's not readable, we do it manually
        $stream = $this->stream->detach();
        $fp = fopen($targetPath, 'wb');
        stream_copy_to_stream($stream, $fp);
        fclose($fp);
        fclose($stream);
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * {@inheritDoc}
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritDoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}