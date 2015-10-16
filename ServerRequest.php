<?php

namespace Tale\Http;

use Psr\Http\Message\An;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class ServerRequest extends Request implements ServerRequestInterface
{

    private $_sapiType;
    private $_attributes;
    private $_queryParams;
    private $_serverParams;
    private $_cookieParams;
    private $_uploadedFiles;
    private $_parsedBody;

    public function __construct(
        array $attributes = null,
        array $queryParams = null,
        array $serverParams = null,
        array $cookieParams = null
    )
    {

        $this->_sapiType = php_sapi_name();
        $this->_attributes = $attributes ? $attributes : [];
        //The Server Params are the most important, since most other values
        //in here pull their default values from it
        $this->_serverParams = $serverParams
                             ? $serverParams
                             : $_SERVER;
        $this->_queryParams = $queryParams
                            ? $queryParams
                            : $this->_getQueryParams(); //Has a CLI fallback!
        $this->_cookieParams = $cookieParams
                             ? $cookieParams
                             : $_COOKIE;
        $this->_uploadedFiles = !empty($_FILES)
                              ? $this->_filterUploadedFiles($_FILES)
                              : [];
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

        return $this->_cookieParams;
    }

    public function getCookieParam($name, $default = null)
    {

        return isset($this->_cookieParams[$name])
             ? $this->_cookieParams[$name]
             : $default;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getQueryParams()
    {

        return $this->_queryParams;
    }

    public function getQueryParam($name, $default = null)
    {

        return isset($this->_queryParams[$name])
            ? $this->_queryParams[$name]
            : $default;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getUploadedFiles()
    {

        return $this->_uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {

        $request = clone $this;
        $request->_uploadedFiles = array_replace(
            $request->_queryParams,
            $query
        );
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {

        if ($this->_parsedBody)
            return $this->_parsedBody;

        $contentType = $this->getHeaderLine('content-type');
        if (!$this->isCli() && $this->isPost() && in_array(
            $this->getHeaderLine('content-type'),
            ['multipart/form-data', 'application/x-www-form-urlencoded']
        )) {

            //Why even bother doing anything?
            //Notice that php://input is not populated on
            //multipart/form-data anyways!
            $this->_parsedBody = $_POST;
            return $this->_parsedBody;
        }

        $body = $this->getBody();

        if ($body->eof()) {

            //empty body, we got nothin!
            return null;
        }

        switch(strtolower($contentType)) {
            case 'application/json':

                $this->_parsedBody = json_decode($body);
                break;
            case 'text/xml':

                $this->_parsedBody = simplexml_load_string($body);
                break;
            default:

                parse_str($body, $this->_parsedBody);
                break;
        }

        $body->rewind();
        return $this->_parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {

        if (!is_array($data) || !is_object($data))
            throw new \InvalidArgumentException(
                "Structured data should either be an object or an array"
            );

        $request = clone $this;

        $request->_parsedBody = $data;
        $newBody = Stream::createMemoryStream();

        $contentType = $this->getHeaderLine('content-type');

        if (!empty($data)) {

            switch (strtolower($contentType)) {
                case 'application/json':

                    $newBody->write(json_encode($data));
                    break;
                case 'text/xml':

                    throw new \RuntimeException(
                        "The automatic XML conversion is not supported right now"
                    );
                    break;
                default:

                    $newBody->write(http_build_query((array)$data));
                    break;
            }
        }

        $newBody->rewind();
        return $request->withBody($newBody);
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {

        return $this->_attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {

        return isset($this->_attributes[$name])
             ? $this->_attributes[$name]
             : $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        $request->_attributes[$name] = $value;

        return $request;
    }

    public function withAttributes(array $attributes)
    {

        $request = clone $this;
        $request->_attributes = array_replace(
            $request->_attributes,
            $attributes
        );

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {

        $request = clone $this;
        unset($request->_attributes[$name]);

        return $request;
    }

    public function isCli()
    {

        return !empty($this->_sapiType)
            && strncmp('cli', strtolower($this->_sapiType), 3) === 0;
    }

    private function _getUri()
    {

        if ($this->isCli()) {

            //In a CLI environment we kinda fake our whole
            //HTTP environment to get something as close as possible
            //to let normal applications run
            //If the first argument is a path, it gets taken as the path

            $uri = new Uri('http://localhost');

            //Notice that $this->_getQueryParams gets those from the
            //passed CLI args if necessary
            if (count($this->_queryParams) > 0)
                $uri = $uri->withQueryArray($this->_queryParams);


            if (isset($this->_queryParams[1]))
                $uri = $uri->withPath($this->_queryParams[1]);

            return $uri;
        }

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

        return $this->getServerParam(
            'REQUEST_METHOD',
            self::DEFAULT_METHOD //fallback for CLI envs
        );
    }

    private function _getBody()
    {

        return Stream::createInputStream();
    }

    private function _getHeaders()
    {

        //If we're in a CLI environment, we don't have to look for headers
        //There's no easy way to define any.
        if ($this->isCli())
            return [];

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
            'SERVER_PROTOCOL',
            'HTTP/'.MessageBase::DEFAULT_VERSION //Default for CLI environments
        ));
        return $version;
    }

    private function _getQueryParams()
    {

        if (!$this->isCli())
            return $_GET;

        //We're in a CLI environment, so $_GET variables cant really be set
        //We kinda fake them by creating an associative array out of our
        //passed command-line-arguments and pass them as query parameters

        //If there are no arguments, there are no arguments!
        if (empty($_SERVER['argv']))
            return [];

        $currentOption = null;
        $args = [];
        foreach ($_SERVER['argv'] as $i => $value) {

            if (strlen($value) > 0 && $value[0] === '-') {

                if ($currentOption) {

                    //Old current-option is finished and has no value
                    $args[$currentOption] = true;
                    $currentOption = null;
                }

                //This is a key (We don't care if short or long)
                $currentOption = ltrim($value, '-');
                continue;
            }

            if ($currentOption) {

                $args[$currentOption] = $value;
                $currentOption = null;
                continue;
            }

            $args[$i] = $value;
        }

        return $args;
    }

    private function _filterUploadedFiles(array $files)
    {

        $result = [];
        foreach ($files as $key => $fileInfo) {

            if ($fileInfo instanceof UploadedFileInterface) {

                $result[$key] = $fileInfo;
                continue;
            }

            if (is_array($fileInfo) && isset($fileInfo['tmp_name'])) {

                $result[$key] = $this->_filterUploadedFile($fileInfo);
                continue;
            }

            if (is_array($fileInfo)) {

                $result[$key] = $this->_filterUploadedFiles($fileInfo);
            }
        }

        return $result;
    }

    private function _filterUploadedFile(array $fileInfo)
    {

        if (is_array($fileInfo['tmp_name'])) {

            return $this->_filterNestedUploadedFiles($fileInfo);
        }

        return new UploadedFile(
            $fileInfo['tmp_name'],
            $fileInfo['size'],
            $fileInfo['error'],
            $fileInfo['name'],
            $fileInfo['type']
        );
    }

    private function _filterNestedUploadedFiles(array $files)
    {

        $result = [];
        foreach (array_keys($files['tmp_name']) as $key) {

            $fileInfo = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key]
            ];

            $result[$key] = $this->_filterUploadedFile($fileInfo);
        }

        return $result;
    }

    public function __isset($attributeName)
    {

        return isset($this->_attributes[$attributeName]);
    }

    public function __get($attributeName)
    {

        return $this->getAttribute($attributeName);
    }
}