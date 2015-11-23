<?php

namespace Tale\Http;

use Psr\Http\Message\RequestInterface;

class Client
{

    private $_options;

    public function __construct(array $options = null)
    {

        $this->_options = array_replace_recursive([
            'headers' => [],
            'timeOut' => 3,
            'bufferSize' => 1024,
            'baseUri' => null
        ], $options ? $options : []);
    }

    public function send(RequestInterface $request)
    {

        $baseUri = new Uri($this->_options['baseUri']);
        $body = $request->getBody();
        $size = $body->getSize();
        $uri = $request->getUri();
        $path = $baseUri->getPath().$uri->getPath();
        $query = $uri->getQuery();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();
        $hasBody = $size > 0;

        if (empty($scheme))
            $scheme = $baseUri->getScheme();

        if (empty($host))
            $host = $baseUri->getHost();

        if (!$request->hasHeader('host'))
            $request = $request->withHeader('Host', $host);

        if (empty($port))
            $port = $baseUri->getPort();

        if ($scheme === 'https') {

            $host = "tls://$host";
            if (empty($port))
                $port = 443;
        } else if (empty($port))
            $port = 80;

        if (empty($host))
            throw new \Exception(
                "Failed to send request: No host given in the URI"
            );

        foreach ($this->_options['headers'] as $name => $value)
            $request = $request->withHeader($name, $value);

        if ($hasBody) {

            $request = $request->withHeader('Content-Length', [$size]);
        }

        $request = $request->withHeader('Connection', ['close']);

        $socket = @fsockopen($host, $port, $errNo, $errStr, $this->_options['timeOut']);

        if (!$socket)
            throw new \Exception("Failed to connect socket to [$host:$port]: $errStr");


        $crlf = "\r\n";
        fwrite($socket, implode(' ', [
            $request->getMethod(),
            ($path ? $path : '/').(!empty($query) ? "?$query" : ''),
            'HTTP/'.$request->getProtocolVersion()
        ]).$crlf);

        foreach ($request->getHeaders() as $name => $value) {

            fwrite($socket, "$name: ".$request->getHeaderLine($name).$crlf);
        }

        fwrite($socket, $crlf);

        if ($hasBody) {

            $body->rewind();
            while (!$body->eof()) {

                fwrite($socket, $body->read($this->_options['bufferSize']));
            }
        }

        $responseContent = stream_get_contents($socket);

        if (strncmp($responseContent, 'HTTP/', 5) !== 0)
            throw new \Exception(
                "Failed to get response: Response is no HTTP response"
            );

        $parts = explode($crlf.$crlf, $responseContent, 2);

        $headerLines = explode($crlf, $parts[0]);
        $body = $parts[1] ? $parts[1] : '';

        list($protocol, $statusCode, $reasonPhrase) = explode(' ', $headerLines[0], 3);
        list($protocolName, $protocolVersion) = explode('/', $protocol, 2);

        unset($headerLines[0]);

        $headers = [];
        foreach ($headerLines as $line) {

            list($name, $value) = explode(':', $line, 2);

            $headers[trim($name)] = trim($value);
        }

        return new Response(
            $body ? new StringStream($body) : null,
            intval($statusCode),
            $headers,
            $reasonPhrase,
            $protocolVersion
        );
    }

    public function request($method, $uri, array $data = null, array $headers = null, $protocolVersion = null)
    {

        $data = $data ? $data : [];
        $headers = $headers ? $headers : [];
        $body = null;

        $uri = new Uri($uri);

        if ($method === Method::GET && $data) {

            $query = $uri->getQuery();

            if ($query) {

                parse_str($query, $args);
                $uri = $uri->withQuery(http_build_query(array_replace_recursive($args, $data), '', '&', \PHP_QUERY_RFC3986));
            } else {

                $uri = $uri->withQuery(http_build_query($data, '', '&', \PHP_QUERY_RFC3986));
            }
        } else if ($data) {

            $body = new StringStream(http_build_query($data), '', '&', \PHP_QUERY_RFC1738);
        }

        $request = new Request($uri, $method, $body, $headers, $protocolVersion);
        return $this->send($request);
    }

    public function get($uri, array $data = null, array $headers = null, $protocolVersion = null)
    {

        return $this->request(Method::GET, $uri, $data, $headers, $protocolVersion);
    }

    public function post($uri, array $data = null, array $headers = null, $protocolVersion = null)
    {

        return $this->request(Method::POST, $uri, $data, $headers, $protocolVersion);
    }

    public function put($uri, array $data = null, array $headers = null, $protocolVersion = null)
    {

        return $this->request(Method::PUT, $uri, $data, $headers, $protocolVersion);
    }

    public function delete($uri, array $data = null, array $headers = null, $protocolVersion = null)
    {

        return $this->request(Method::DELETE, $uri, $data, $headers, $protocolVersion);
    }
}