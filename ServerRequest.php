<?php

namespace Tale\Http;

use Psr\Http\Message\An;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class ServerRequest extends Request implements ServerRequestInterface
{

    private $_attributes;
    private $_serverParams;
    private $_cookieParams;
    private $_queryParams;
    private $_uploadedFiles;
    private $_parsedBody;

    public function __construct(
        array $attributes = null,
        array $serverParams = null,
        array $cookieParams = null,
        array $queryParams = null
    )
    {

        $this->_attributes = $attributes ? $attributes : [];
        $this->_serverParams = $_SERVER;
        $this->_cookieParams = $_COOKIE;
        $this->_queryParams = $_GET;
        $this->_uploadedFiles = $_FILES;
        $this->_parsedBody = null;

        parent::__construct(
            $this->_getUri(),
            $this->_getMethod(),
            $this->_getBody(),
            $this->_getHeaders(),
            $this->_getProtocolVersion()
        );
    }

    /**
     * @inheritDoc
     */
    public function getServerParams()
    {

        return $this->_serverParams;
    }

    public function getServerParam($name, $default = null)
    {

        return isset($this->_serverParams[$name])
            ? $this->_serverParams[$name]
            : $default;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        // TODO: Implement getCookieParams() method.
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        // TODO: Implement withCookieParams() method.
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        // TODO: Implement getQueryParams() method.
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        // TODO: Implement withQueryParams() method.
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        // TODO: Implement getUploadedFiles() method.
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        // TODO: Implement withUploadedFiles() method.
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        // TODO: Implement getParsedBody() method.
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        // TODO: Implement withParsedBody() method.
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        // TODO: Implement getAttributes() method.
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        // TODO: Implement getAttribute() method.
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        // TODO: Implement withAttribute() method.
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        // TODO: Implement withoutAttribute() method.
    }

    private function _getUri()
    {

        $uri = new Uri();

        $scheme = 'http';
        $https = $this->getServerParam('HTTPS');
        if ($https && $https !== 'off')
            $scheme = 'https';

        $uri = $uri->withScheme($scheme);

        $host = $this->getServerParam(
            'HTTP_HOST',
            $this->getServerParam('SERVER_NAME')
        );

        if (!empty($host))
            $uri = $uri->withHost($host);

        $port = $this->getServerParam('SERVER_PORT');
        if (!empty($port))
            $uri = $uri->withPort($port);

        $path = $this->getServerParam('PATH_INFO');
        if (empty($path)) {

            $path = $this->getServerParam(
                'REDIRECT_REQUEST_URI',
                $this->getServerParam('REQUEST_URI')
            );
        }

        if (empty($path))
            $path = '/';

        $pos = null;
        if (($pos = strpos($path, '?')) !== false)
            $path = substr($path, 0, $pos);

        $uri = $uri->withPath($path);

        $query = $this->getServerParam(
            'REDIRECT_QUERY_STRING',
            $this->getServerParam('QUERY_STRING')
        );

        if (!empty($query))
            $uri = $uri->withQuery($query);

        return $uri;
    }

    private function _getMethod()
    {

        return $this->getServerParam('REQUEST_METHOD');
    }

    private function _getBody()
    {

        return Stream::createInput();
    }

    private function _getHeaders()
    {

        $headers = [];
        foreach ($this->_serverParams as $name => $value) {

            if (strncmp($name, 'HTTP_', 5) === 0) {

                $name = implode('-', array_map(
                    'ucfirst',
                    explode('_', strtolower(substr($name, 5)))
                ));
                $headers[$name] = $value;
                continue;
            }

            if (strncmp($name, 'CONTENT_', 8) === 0) {

                $name = implode('-', array_map(
                    'ucfirst',
                    explode('_', strtolower($name))
                ));

                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    private function _getProtocolVersion()
    {

        list(, $version) = explode('/', $this->getServerParam(
            'SERVER_PROTOCOL'
        ));
        return $version;
    }
}