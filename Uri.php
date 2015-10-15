<?php

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{

    private static $_schemes = [
        'http' => 80,
        'https' => 443
    ];

    private $_scheme;
    private $_user;
    private $_password;
    private $_host;
    private $_port;
    private $_path;
    private $_query;
    private $_fragment;

    private $_uriString;


    public function __construct($uriString = null)
    {

        $this->_scheme = null;
        $this->_user = null;
        $this->_password = null;
        $this->_host = null;
        $this->_port = null;
        $this->_path = null;
        $this->_query = [];
        $this->_fragment = null;
        $this->_uriString = null;

        if ($uriString !== null)
            $this->_parse($uriString);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {

        if (empty($this->_scheme))
            return '';

        return $this->_scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority()
    {

        $host = $this->getHost();

        if (empty($host))
            return '';

        $userInfo = $this->getUserInfo();
        $port = $this->getPort();

        $authority = '';

        if (!empty($userInfo))
            $authority .= "$userInfo@";

        $authority .= $host;

        if (!empty($port))
            $authority .= ":$port";

        return $authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo()
    {

        if (empty($this->_user))
            return '';

        $userInfo = $this->_user;

        if (!empty($this->_password))
            $userInfo .= ":{$this->_password}";

        return $userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {

        if (empty($this->_host))
            return '';

        return $this->_host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {

        $scheme = $this->getScheme();
        if (empty($this->_port) || (
            (isset(self::$_schemes[$scheme]) && $this->_port === self::$_schemes[$scheme])
        )) {

            return null;
        }

        return $this->_port;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {

        if (empty($this->_path))
            return '';

        return $this->_path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {

        if (empty($this->_query))
            return '';

        return $this->_query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment()
    {
        if (empty($this->_fragment))
            return '';

        return $this->_fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme)
    {

        $uri = clone $this;
        $uri->_scheme = $this->_filterScheme($scheme);

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null)
    {

        $uri = clone $this;
        $uri->_user = !empty($user) ? $user : null;
        $uri->_password = !empty($password) ? $password : null;

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host)
    {

        $uri = clone $this;
        $uri->_host = $this->_filterHost($host);

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port)
    {
        $uri = clone $this;
        $uri->_port = $this->_filterPort($port);

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {

        $uri = clone $this;
        $uri->_path = $this->_filterPath($path);

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {

        $uri = clone $this;
        $uri->_query = $this->_filterQuery($query);

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment)
    {
        $uri = clone $this;
        $uri->_fragment = $this->_filterFragment($fragment);

        return $uri;
    }

    private function _parse($uriString)
    {

        if (!is_string($uriString))
            throw new InvalidArgumentException(
                "Passed URI string needs to be a string value"
            );

        $parsed = parse_url($uriString);

        if (!is_array($parsed))
            throw new InvalidArgumentException(
                "Passed URI string doesn't seem to be a valid URI"
            );

        $parts = array_replace([
            'scheme' => null,
            'user' => null,
            'pass' => null,
            'host' => null,
            'port' => null,
            'path' => null,
            'query' => null,
            'fragment' => null
        ], $parsed);

        if ($parts['scheme'])
            $this->_scheme = $this->_filterScheme($parts['scheme']);

        if ($parts['user'])
            $this->_user = $parts['user'];

        if ($parts['pass'])
            $this->_password = $parts['pass'];

        if ($parts['host'])
            $this->_host = $this->_filterHost($parts['host']);

        if ($parts['port'])
            $this->_port = $this->_filterPort($parts['port']);

        if ($parts['path'])
            $this->_path = $this->_filterPath($parts['path']);

        if ($parts['query'])
            $this->_query = $this->_filterQuery($parts['query']);

        if ($parts['fragment'])
            $this->_fragment = $this->_filterFragment($parts['fragment']);
    }

    private function _filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (empty($scheme)) {

            return null;
        }

        if (!isset(self::$_schemes[$scheme])) {

            throw new InvalidArgumentException(
                "Unsupported scheme $scheme, allowed schemes are ".implode(
                    ', ',
                    array_keys(self::$_schemes)
                )
            );
        }

        return $scheme;
    }

    private function _filterHost($host)
    {

        return !empty($host) ? strtolower($host) : null;
    }

    private function _filterPort($port)
    {

        if (empty($port)) {

            return null;
        }

        if (!(is_integer($port) || (is_string($port) && is_numeric($port)))) {
            throw new InvalidArgumentException(
                "Port needs to be valid integer or numeric string"
            );
        }

        $port = intval($port);

        if ($port < 1 || $port > 65535)
            throw new InvalidArgumentException(
                "Port needs to be a valid TCP/UDP port between 1 and 65535"
            );

        return $port;
    }

    private function _filterPath($path)
    {

        if (empty($path))
            return null;

        if (strpos($path, '#') !== false || strpos($path, '?') !== false)
            throw new InvalidArgumentException(
                "The passed path shouldn't contain a query or fragment"
            );

        $authority = $this->getAuthority();
         if (empty($authority) && strncmp($path, '//', 2) === 0)
            $path = '/'.ltrim($path, '/');

        return $this->_encode($path);
    }

    private function _filterQuery($query)
    {

        if (empty($query)) {

            return null;
        }

        $query = strval($query);

        if ($query[0] === '?')
            $query = substr($query, 1 );

        $pairs = explode('&', $query);
        foreach ($pairs as $i => $pair) {

            list($key, $value) = array_pad(explode('=', $pair), 2, null);

            if ($value === null) {

                $pairs[$i] = $this->_encode($key, true);
                continue;
            }

            $pairs[$i] = $this->_encode($key, true).'='.$this->_encode($value, true);
        }

        return implode('&', $pairs);
    }

    private function _filterFragment($fragment)
    {

        if (empty($fragment)) {

            return null;
        }

        $fragment = strval($fragment);

        if ($fragment[0] === '#')
            $fragment = substr($fragment, 1 );

        return $this->_encode($fragment);
    }

    private function _encode($value, $withDelimeters = false)
    {

        $delims = $withDelimeters ? '!\'\(\)\*' : '';

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~\+;,=\$&%:@\/\?'.$delims.']+|%(?![A-Fa-f0-9]{2}))/',
            function($matches) {

                return rawurlencode($matches[0]);
            },
            $value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {

        if ($this->_uriString !== null)
            return $this->_uriString;

        $scheme = $this->getScheme();
        if (!empty($scheme))
            $scheme .= ':';

        $authority = $this->getAuthority();
        if (!empty($authority))
            $authority = '//'.$authority;

        $path = ltrim($this->getPath(), '/');
        if (!empty($path))
            $path = '/'.$path;

        $query = $this->getQuery();
        if (!empty($query))
            $query = '?'.$query;

        $fragment = $this->getFragment();
        if (!empty($fragment))
            $fragment = '#'.$fragment;

        $this->_uriString = implode('', [
            $scheme, $authority, $path, $query, $fragment
        ]);

        return $this->_uriString;
    }

    public function __clone()
    {

        $this->_uriString = null;
    }
}