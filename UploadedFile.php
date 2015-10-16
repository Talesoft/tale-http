<?php

namespace Tale\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{

    const ERROR_OK = \UPLOAD_ERR_OK;
    const ERROR_INI_SIZE = \UPLOAD_ERR_INI_SIZE;
    const ERROR_FORM_SIZE = \UPLOAD_ERR_FORM_SIZE;
    const ERROR_PARTIAL = \UPLOAD_ERR_PARTIAL;
    const ERROR_NO_FILE = \UPLOAD_ERR_NO_FILE;
    const ERROR_NO_TMP_DIR = \UPLOAD_ERR_NO_TMP_DIR;
    const ERROR_CANT_WRITE = \UPLOAD_ERR_CANT_WRITE;
    const ERROR_EXTENSION = \UPLOAD_ERR_EXTENSION;


    private $_path;
    private $_size;
    private $_error;
    private $_clientFilename;
    private $_clientMediaType;


    public function __construct(
        $path,
        $size,
        $error,
        $clientFilename,
        $clientMediaType
    )
    {

        $this->_path = $path;
        $this->_size = intval($size);
        $this->_error = intval($error);
        $this->_clientFilename = $clientFilename;
        $this->_clientMediaType = $clientMediaType;
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {

        if (!file_exists($this->_path))
            throw new \RuntimeException(
                "The uploaded file has already been moved"
            );

        return new Stream($this->_path, 'rb+');
    }

    /**
     * @inheritDoc
     *
     * @TODO: Writable checks
     */
    public function moveTo($targetPath)
    {

        $sapi = \PHP_SAPI;
        if (empty($sapi) || strncmp('cli', $sapi, 3) === 0) {

            file_put_contents($targetPath, strval($this->getStream()));
        } else {

            move_uploaded_file($this->_path, $targetPath);
        }

        unlink($this->_path);
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {

        return $this->_size;
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {

        return $this->_error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename()
    {

        return $this->_clientFilename;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {

        return $this->_clientMediaType;
    }
}