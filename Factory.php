<?php

namespace Tale\Http;

use Psr\Http\Message\UploadedFileInterface;
use Tale\Stream;

final class Factory
{

    public static function getServerParam($name, $default = null)
    {

        return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
    }

    public static function getUri()
    {

        $uri = new Uri();

        $scheme = 'http';
        $https = self::getServerParam('HTTPS');
        if ($https && $https !== 'off')
            $scheme = 'https';

        $uri = $uri->withScheme($scheme);

        $host = self::getServerParam(
            'HTTP_HOST',
            self::getServerParam('SERVER_NAME')
        );

        if (!empty($host))
            $uri = $uri->withHost($host);

        $port = self::getServerParam('SERVER_PORT');
        if (!empty($port))
            $uri = $uri->withPort($port);

        $path = self::getServerParam('PATH_INFO');
        if (empty($path)) {

            $path = self::getServerParam(
                'REDIRECT_REQUEST_URI',
                self::getServerParam('REQUEST_URI')
            );
        }

        if (empty($path))
            $path = '/';

        $pos = null;
        if (($pos = strpos($path, '?')) !== false)
            $path = substr($path, 0, $pos);

        $uri = $uri->withPath($path);

        $query = self::getServerParam(
            'REDIRECT_QUERY_STRING',
            self::getServerParam('QUERY_STRING')
        );

        if (!empty($query))
            $uri = $uri->withQuery($query);

        return $uri;
    }

    public static function getMethod()
    {

        return self::getServerParam(
            'REQUEST_METHOD',
            Method::GET
        );
    }

    public static function getBody()
    {

        return new Stream\InputStream();
    }

    public static function getHeaders()
    {

        $headers = [];
        foreach ($_SERVER as $name => $value) {

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

    public static function getProtocolVersion()
    {

        list(, $version) = explode('/', self::getServerParam(
            'SERVER_PROTOCOL',
            'HTTP/'.MessageBase::DEFAULT_VERSION
        ));

        return $version;
    }

    public static function getQueryParams()
    {

        if (!isset($_GET))
            return [];

        return $_GET;
    }

    public static function getCookieParams()
    {

        if (!isset($_COOKIE))
            return [];

        return $_COOKIE;
    }

    public static function getUploadedFiles()
    {

        if (!isset($_FILES))
            return [];

        return self::_filterUploadedFiles($_FILES);
    }

    private static function _filterUploadedFiles(array $files)
    {

        $result = [];
        foreach ($files as $key => $fileInfo) {

            if ($fileInfo instanceof UploadedFileInterface) {

                $result[$key] = $fileInfo;
                continue;
            }

            if (is_array($fileInfo) && isset($fileInfo['tmp_name'])) {

                $result[$key] = self::_filterUploadedFile($fileInfo);
                continue;
            }

            if (is_array($fileInfo)) {

                $result[$key] = self::_filterUploadedFiles($fileInfo);
            }
        }

        return $result;
    }

    private static function _filterUploadedFile(array $fileInfo)
    {

        if (is_array($fileInfo['tmp_name'])) {

            return self::_filterNestedUploadedFiles($fileInfo);
        }

        return new UploadedFile(
            $fileInfo['tmp_name'],
            $fileInfo['size'],
            $fileInfo['error'],
            $fileInfo['name'],
            $fileInfo['type']
        );
    }

    private static function _filterNestedUploadedFiles(array $files)
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

            $result[$key] = self::_filterUploadedFile($fileInfo);
        }

        return $result;
    }

    public static function getParsedBody()
    {

        $headers = self::getHeaders();
        $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : null;
        if (
            (self::getMethod() === Method::POST)
            && in_array(
                $contentType,
                ['multipart/form-data', 'application/x-www-form-urlencoded']
            )) {

            return $_POST;
        }

        $body = self::getBody();
        if ($body->eof()) {

            //empty body, we got nothin!
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
        return $data;
    }

    public static function getServerRequest(array $attributes = null)
    {

        return new ServerRequest(
            self::getUri(),
            self::getMethod(),
            self::getBody(),
            self::getHeaders(),
            self::getProtocolVersion(),
            $_SERVER,
            self::getQueryParams(),
            self::getCookieParams(),
            self::getUploadedFiles(),
            self::getParsedBody(),
            $attributes
        );
    }
}