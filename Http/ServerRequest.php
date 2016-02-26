<?php

namespace Tale\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class ServerRequest extends Request implements ServerRequestInterface
{

    private $_attributes;
    private $_queryParams;
    private $_serverParams;
    private $_cookieParams;
    private $_uploadedFiles;
    private $_parsedBody;

    public function __construct(
        $uri = null,
        $method = null,
        StreamInterface $body = null,
        array $headers = null,
        $protocolVersion = null,
        array $serverParams = null,
        array $queryParams = null,
        array $cookieParams = null,
        array $uploadedFiles = null,
        array $parsedBody = null,
        array $attributes = null
    )
    {

        parent::__construct(
            $uri,
            $method,
            $body,
            $headers,
            $protocolVersion
        );

        $this->_attributes = $attributes ? $attributes : [];
        $this->_serverParams = $serverParams ? $serverParams : [];
        $this->_queryParams = $queryParams ? $queryParams : [];
        $this->_cookieParams = $cookieParams ? $cookieParams : [];
        $this->_uploadedFiles = $uploadedFiles ? $uploadedFiles : [];
        $this->_parsedBody = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getServerParams()
    {

        return $this->_serverParams;
    }

    /**
     * {@inheritDoc}
     */
    public function getCookieParams()
    {

        return $this->_cookieParams;
    }

    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function withCookieParams(array $cookies)
    {

        $request = clone $this;
        $request->_cookieParams = array_replace(
            $request->_cookieParams,
            $cookies
        );

        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryParams()
    {

        return $this->_queryParams;
    }

    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function withQueryParams(array $query)
    {

        $request = clone $this;
        $request->_queryParams = array_replace(
            $request->_queryParams,
            $query
        );

        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getUploadedFiles()
    {

        return $this->_uploadedFiles;
    }

    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles)
    {

        $request = clone $this;
        $request->_uploadedFiles = array_replace_recursive(
            $request->_uploadedFiles,
            $uploadedFiles
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getParsedBody()
    {

        return $this->_parsedBody;
    }

    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function withParsedBody($data)
    {

        if (!is_array($data) || !is_object($data))
            throw new \InvalidArgumentException(
                "Structured data should either be an object or an array"
            );

        $request = clone $this;
        $request->_parsedBody = $data;
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {

        return $this->_attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($name, $default = null)
    {

        return isset($this->_attributes[$name])
             ? $this->_attributes[$name]
             : $default;
    }

    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        $request->_attributes[$name] = $value;

        return $request;
    }

    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function withoutAttribute($name)
    {

        $request = clone $this;
        unset($request->_attributes[$name]);

        return $request;
    }
}