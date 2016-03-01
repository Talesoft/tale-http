<?php
/**
 * The Tale HTTP URI-Utility.
 *
 * Contains a PSR-7 compatible URI-implementation and provides
 * useful utilities to construct and destruct URIs.
 *
 * This file is part of the Tale HTTP Utility Library.
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   HTTP, Utilities
 * @package    Tale\Http
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.2
 * @link       http://http.talesoft.io/docs/files/Uri.html
 * @since      File available since Release 1.0
 */

namespace Tale\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Acts as a wrapper for all kind of URIs (Including URNs and URLs)
 *
 * Compatible to the UriInterface of PSR-7
 *
 * Examples:
 * <code>
 *
 * $uri = new Uri('http://example.com:8080');
 * $uri = new Uri('/');
 * $uri = new Uri('/some/path/somewhere');
 * $uri = new Uri('urn:some:random:book');
 *
 * </code>
 *
 * {@inheritdoc}
 *
 * @see        https://github.com/php-fig/http-message/blob/master/src/UriInterface.php
 * @category   HTTP, Utilities
 * @package    Tale\Http
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.2
 * @link       http://http.talesoft.io/docs/classes/Tale.Http.Uri.html
 * @since      File available since Release 1.0
 */
class Uri implements UriInterface
{


    /**
     * Contains the schemes that the HTTP abstraction is able to handle.
     *
     * Since this is a HTTP abstraction, there are only HTTP and HTTPS available.
     * More available schemes may be HTTP-based protocols like DAV.
     *
     * @var array
     */
    private static $schemes = [
        'http' => 80,
        'https' => 443
    ];

    /**
     * The scheme this URI contains
     *
     * e.g. {{https}}://example.com
     *
     * @var string|null
     */
    private $scheme;

    /**
     * The user this URI is associated with
     *
     * e.g. {{user}}@example.com
     *
     * @var string|null
     */
    private $user;

    /**
     * The password this URI is associated with
     *
     * e.g. user:{{password}}@example.com
     *
     * @var string|null
     */
    private $password;

    /**
     * The host this URI points to
     *
     * e.g. http://user@{{example.com}}/test
     *
     * @var string|null
     */
    private $host;

    /**
     * The port this URI points to
     *
     * e.g. http://example.com:{{8080}}/test
     *
     * @var string|null
     */
    private $port;

    /**
     * The path this URI points to
     *
     * e.g. http://example.com{{/some/sub/path}}
     *
     * @var string|null
     */
    private $path;

    /**
     * The query string this URI contains
     *
     * e.g. http://example.com/test?{{var1=val1&var2=val2}}
     *
     * @var array
     */
    private $query;

    /**
     * The fragment the URI contains
     *
     * e.g. http://example.com/test#{{someFragment}}
     *
     * @var string|null
     */
    private $fragment;

    /**
     * A cache for the fully generated URI string
     *
     * @var string|null
     */
    private $uriString;


    /**
     * Creates a new URI instance by a passed URI string
     *
     * URI can either be a Path, URL or URN (or any kind of URI)
     *
     * All parts of the URI are optional.
     * The smallest kind of URI is a normal slash ('/')
     *
     * Notice that in order to use other schemes than http and https,
     * you have to add custom schemes with fixed default ports using
     * Uri::registerScheme()
     *
     * Possible formats are (among many others):
     * <samp>
     * /
     * /some/path
     * example.com/some-path
     * example.com:8080
     * https://example.com/test
     * http://example.com?var1=val1&var2=val2
     * https://example.com/test#someSubFragment
     * urn:some:random:book
     * whatever:whatever:whatever?whatever#whatever
     * </samp>
     *
     * @param string|null $uriString the URI string to convert
     */
    public function __construct($uriString = null)
    {

        $this->scheme = null;
        $this->user = null;
        $this->password = null;
        $this->host = null;
        $this->port = null;
        $this->path = null;
        $this->query = [];
        $this->fragment = null;
        $this->uriString = null;

        if ($uriString !== null)
            $this->parse($uriString);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {

        if (empty($this->scheme))
            return '';

        return $this->scheme;
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

        if (empty($this->user))
            return '';

        $userInfo = $this->user;

        if (!empty($this->password))
            $userInfo .= ":{$this->password}";

        return $userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {

        if (empty($this->host))
            return '';

        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {

        $scheme = $this->getScheme();
        if (empty($this->port) || (
            (isset(self::$schemes[$scheme]) && $this->port === self::$schemes[$scheme])
        )) {

            return null;
        }

        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {

        if (empty($this->path))
            return '';

        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {

        if (empty($this->query))
            return '';

        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment()
    {
        if (empty($this->fragment))
            return '';

        return $this->fragment;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function withScheme($scheme)
    {

        $uri = clone $this;
        $uri->scheme = $this->filterScheme($scheme);

        return $uri;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function withUserInfo($user, $password = null)
    {

        $uri = clone $this;
        $uri->user = !empty($user) ? $user : null;
        $uri->password = !empty($password) ? $password : null;

        return $uri;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function withHost($host)
    {

        $uri = clone $this;
        $uri->host = $this->filterHost($host);

        return $uri;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function withPort($port)
    {
        $uri = clone $this;
        $uri->port = $this->filterPort($port);

        return $uri;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function withPath($path)
    {

        $uri = clone $this;
        $uri->path = $this->filterPath($path);

        return $uri;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function withQuery($query)
    {

        $uri = clone $this;
        $uri->query = $this->filterQuery($query);

        return $uri;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function withFragment($fragment)
    {
        $uri = clone $this;
        $uri->fragment = $this->filterFragment($fragment);

        return $uri;
    }

    /**
     * Parses an URI string into the values associated with this URI instance
     *
     * This uses parse_url to parse the given URI string into an array
     * and assigns the values to the current object.
     *
     * The values get filtered correctly by this function.
     *
     * @param string $uriString the string to convert into this URI instance
     */
    private function parse($uriString)
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
            $this->scheme = $this->filterScheme($parts['scheme']);

        if ($parts['user'])
            $this->user = $parts['user'];

        if ($parts['pass'])
            $this->password = $parts['pass'];

        if ($parts['host'])
            $this->host = $this->filterHost($parts['host']);

        if ($parts['port'])
            $this->port = $this->filterPort($parts['port']);

        if ($parts['path'])
            $this->path = $this->filterPath($parts['path']);

        if ($parts['query'])
            $this->query = $this->filterQuery($parts['query']);

        if ($parts['fragment'])
            $this->fragment = $this->filterFragment($parts['fragment']);
    }

    /**
     * Filters a given scheme-name
     *
     * @param mixed $scheme the name of the scheme
     *
     * @return string|null the filtered scheme
     */
    private function filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (empty($scheme)) {

            return null;
        }

        if (!isset(self::$schemes[$scheme])) {

            throw new InvalidArgumentException(
                "Unsupported scheme $scheme, allowed schemes are ".implode(
                    ', ',
                    array_keys(self::$schemes)
                )
            );
        }

        return $scheme;
    }

    /**
     * Filters a given host name
     *
     * @param mixed $host the host name to filter
     *
     * @return string|null the filtered host name
     */
    private function filterHost($host)
    {

        return !empty($host) ? strtolower($host) : null;
    }

    /**
     * Filters a given port number
     *
     * @param mixed $port the port to filter
     *
     * @return int|null the filtered port
     */
    private function filterPort($port)
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

    /**
     * Filters a given path string
     *
     * @param mixed $path the path string to filter
     *
     * @return string|null the filtered path
     */
    private function filterPath($path)
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

        return $this->encode($path);
    }

    /**
     * Filters a given query string
     *
     * @param mixed $query the query string to filter
     *
     * @return string|null the filtered query string
     */
    private function filterQuery($query)
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

                $pairs[$i] = $this->encode($key, true);
                continue;
            }

            $pairs[$i] = $this->encode($key, true).'='.$this->encode($value, true);
        }

        return implode('&', $pairs);
    }

    /**
     * Filters a given fragment string
     *
     * @param mixed $fragment the fragment string
     *
     * @return string|null the filtered fragment
     */
    private function filterFragment($fragment)
    {

        if (empty($fragment)) {

            return null;
        }

        $fragment = strval($fragment);

        if ($fragment[0] === '#')
            $fragment = substr($fragment, 1 );

        return $this->encode($fragment);
    }

    /**
     * Encodes a value and makes sure it's not double-encoded
     *
     * The following characters DON'T get encoded:
     * a-z, A-Z, 0-9, _, -, ., ~, +, ;, ,, =, $, &, %, :, @, /, ?
     *
     * If the second parameter is passed, the characters
     * !, ', (, ) and * won't be encoded as well
     *
     * @param string $value the value to encode
     * @param bool|false $withDelimeters Allow extended delimeters
     *
     * @return string the encoded value
     */
    private function encode($value, $withDelimeters = false)
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

        if ($this->uriString !== null)
            return $this->uriString;

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

        $this->uriString = implode('', [
            $scheme, $authority, $path, $query, $fragment
        ]);

        return $this->uriString;
    }

    /**
     * Makes sure that the cached string-representation
     * of the current URI instance is reset upon cloning.
     */
    public function __clone()
    {

        $this->uriString = null;
    }

    /**
     * Registers a new scheme to allow in URL strings.
     *
     * The port appended will be used as the default port
     * for URIs with the respective scheme
     *
     * @param string $name the name of the scheme
     * @param int $port the port associated with this scheme
     */
    public static function registerScheme($name, $port)
    {

        self::$schemes[$name] = $port;
    }

    /**
     * Removes a registered scheme from the scheme register.
     *
     * Notice that once you removed the scheme, URIs using that scheme
     * may throw errors
     *
     * @param string $name the name of the scheme to remove
     */
    public static function unregisterScheme($name)
    {

        unset(self::$schemes[$name]);
    }
}