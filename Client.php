<?php

namespace Tale\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{

    private $_options;

    public function __construct(array $options = null)
    {

        $this->_options = array_replace_recursive([
            'headers' => [],
            'timeOut' => 3,
            'bufferSize' => 8192,
            'baseUri' => null,
            'responseClassName' => Response::class
        ], $options ? $options : []);

        if (!is_subclass_of($this->_options['responseClassName'], ResponseInterface::class))
            throw new \Exception(
                "The passed response class doesnt comply to the PSR-7 ResponseInterface standard"
            );
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

        $initialHeader = fgets($socket, $this->_options['bufferSize']);

        if (strncmp($initialHeader, 'HTTP/', 5) !== 0)
            throw new \Exception(
                "Failed to get response: Response is no HTTP response"
            );


        $className = $this->_options['responseClassName'];
        $response = new $className;
        list($protocol, $statusCode, $reasonPhrase) = explode(' ', $initialHeader, 3);
        list($protocolName, $protocolVersion) = explode('/', $protocol, 2);

        $response = $response->withProtocolVersion($protocolVersion);

        while ($line = fgets($socket, $this->_options['bufferSize'])) {

            if ($line === $crlf)
                break;

            list($name, $value) = explode(':', $line, 2);

            $response = $response->withHeader(trim($name), [trim($value)]);
        }

        if ($response->hasHeader('content-length')) {

            $response = $response->withBody(
                new StringStream(fread($socket, intval($response->getHeaderLine('content-length'))))
            );
        } else if ($response->hasHeader('transfer-encoding')) {

            switch ($response->getHeaderLine('transfer-encoding')) {
                case 'chunked':

                    $stream = Stream::createTempStream(null, $this->_options['bufferSize']);

                    while ($line = fgets($socket, $this->_options['bufferSize'])) {

                        if ($line === $crlf)
                            continue;

                        $length = hexdec(trim($line));

                        if (!is_int($length))
                            throw new \Exception(
                                "Invalid chunked-encoded body encountered: Length is not hexadecimal"
                            );

                        if ($length < 1 || feof($socket))
                            break;

                        while ($length > 0) {

                            $bufferSize = min($length, $this->_options['bufferSize']);
                            $chunk = fread($socket, $bufferSize);
                            $stream->write($chunk);
                            $length -= $bufferSize;
                        }

                        $token = fread($socket, 2);

                        if ($token !== $crlf)
                            throw new \Exception(
                                "Invalid chunked-encoded body encountered: Chunk didn't end with CRLF"
                            );
                    }

                    $response = $response->withBody($stream);
                    break;
                default:
                    throw new \Exception(
                        "Failed to parse body: Transfer-encoding type not supported"
                    );
            }
        }

        return $response;
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