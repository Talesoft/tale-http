<?php
declare(strict_types=1);

namespace Tale\Test\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Tale\Http\Request;
use function Tale\stream_create_memory;
use function Tale\uri;
use function Tale\uri_parse;

class RequestTest extends TestCase
{
    /** @var Request */
    private $request;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp()
    {
        $this->request = new Request();
    }

    public function testMethodIsGetByDefault()
    {
        $this->assertEquals(Request::METHOD_GET, $this->request->getMethod());
    }

    public function testMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request = $this->request->withMethod('POST');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals(Request::METHOD_POST, $request->getMethod());
    }

    public function testRequestTargetIsSlashByDefault()
    {
        $this->assertEquals('/', $this->request->getRequestTarget());
    }

    public function invalidUrls()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['foo']],
            'object' => [(object) ['foo']],
        ];
    }

    public function testWithUriReturnsNewInstanceWithNewUri()
    {
        $request = $this->request->withUri(uri_parse('https://example.com:10082/foo/bar?baz=bat'));
        $this->assertNotSame($this->request, $request);
        $request2 = $request->withUri(uri_parse('/baz/bat?foo=bar'));
        $this->assertNotSame($this->request, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertEquals('/baz/bat?foo=bar', (string)$request2->getUri());
    }

    public function testConstructorCanAcceptAllMessageParts()
    {
        $uri     = uri_parse('http://example.com/');
        $body    = stream_create_memory();
        $headers = [
            'x-foo' => ['bar'],
        ];
        $request = Request::createPost($uri, $headers, $body);

        $this->assertSame($uri, $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertSame($body, $request->getBody());
        $testHeaders = $request->getHeaders();
        foreach ($headers as $key => $value) {
            $this->assertArrayHasKey($key, $testHeaders);
            $this->assertEquals($value, $testHeaders[$key]);
        }
    }

    public function invalidRequestUri()
    {
        return [
            'true'     => [ true ],
            'false'    => [ false ],
            'int'      => [ 1 ],
            'float'    => [ 1.1 ],
            'array'    => [ ['http://example.com'] ],
            'stdClass' => [ (object) [ 'href'         => 'http://example.com'] ],
        ];
    }

    public function invalidRequestMethod()
    {
        return [
            'true'       => [ true ],
            'false'      => [ false ],
            'int'        => [ 1 ],
            'float'      => [ 1.1 ],
            'bad-string' => [ 'BOGUS-METHOD' ],
            'array'      => [ ['POST'] ],
            'stdClass'   => [ (object) [ 'method' => 'POST'] ],
        ];
    }

    public function testRequestTargetIsSlashWhenUriHasNoPathOrQuery()
    {
        $request = $this->request->withUri(uri_parse('http://example.com'));
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function requestsWithUri()
    {
        return [
            'absolute-uri' => [
                new Request(Request::METHOD_POST, uri_parse('https://api.example.com/user')),
                '/user'
            ],
            'absolute-uri-with-query' => [
                new Request(Request::METHOD_POST, uri_parse('https://api.example.com/user?foo=bar')),
                '/user?foo=bar'
            ],
            'relative-uri' => [
                new Request(Request::METHOD_GET, uri_parse('/user')),
                '/user'
            ],
            'relative-uri-with-query' => [
                new Request(Request::METHOD_GET, uri_parse('/user?foo=bar')),
                '/user?foo=bar'
            ]
        ];
    }

    /**
     * @dataProvider requestsWithUri
     * @param RequestInterface $request
     * @param string $expected
     */
    public function testReturnsRequestTargetWhenUriIsPresent(RequestInterface $request, string $expected)
    {
        $this->assertEquals($expected, $request->getRequestTarget());
    }

    public function validRequestTargets()
    {
        return [
            'asterisk-form'         => [ '*' ],
            'authority-form'        => [ 'api.example.com' ],
            'absolute-form'         => [ 'https://api.example.com/users' ],
            'absolute-form-query'   => [ 'https://api.example.com/users?foo=bar' ],
            'origin-form-path-only' => [ '/users' ],
            'origin-form'           => [ '/users?id=foo' ],
        ];
    }

    /**
     * @dataProvider validRequestTargets
     */
    public function testCanProvideARequestTarget($requestTarget)
    {
        $request = $this->request->withRequestTarget($requestTarget);
        $this->assertEquals($requestTarget, $request->getRequestTarget());
    }

    public function testRequestTargetDoesNotCacheBetweenInstances()
    {
        $request = $this->request->withUri(uri_parse('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(uri_parse('http://mwop.net/bar/baz'));
        $this->assertNotEquals($original, $newRequest->getRequestTarget());
    }

    public function testGetHeadersContainsHostHeaderIfUriWithHostIsPresent()
    {
        $request = Request::createGet('http://example.com');
        $headers = $request->getHeaders();
        $this->assertArrayHasKey('Host', $headers);
        $this->assertContains('example.com', $headers['Host']);
    }

    public function testGetHeadersContainsNoHostHeaderIfNoUriPresent()
    {
        $request = Request::createGet('');
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHeadersContainsNoHostHeaderIfUriDoesNotContainHost()
    {
        $request = Request::createGet('');
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHostHeaderReturnsUriHostWhenPresent()
    {
        $request = Request::createGet('http://example.com');
        $header = $request->getHeader('host');
        $this->assertEquals(['example.com'], $header);
    }

    public function testGetHostHeaderReturnsEmptyArrayIfNoUriPresent()
    {
        $request = Request::createGet('');
        $this->assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderReturnsEmptyArrayIfUriDoesNotContainHost()
    {
        $request = Request::createGet('');
        $this->assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderLineReturnsUriHostWhenPresent()
    {
        $request = Request::createGet('http://example.com');
        $header = $request->getHeaderLine('host');
        $this->assertContains('example.com', $header);
    }

    public function testGetHostHeaderLineIsEmptyIfNoUriPresent()
    {
        $request = Request::createGet('');
        $this->assertEmpty($request->getHeaderLine('host'));
    }

    public function testPassingPreserveHostFlagWhenUpdatingUriDoesNotUpdateHostHeader()
    {
        $request = Request::createGet('')
            ->withAddedHeader('Host', 'example.com');

        $uri = uri()->withHost('www.example.com');
        $new = $request->withUri($uri, true);

        $this->assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    public function testNotPassingPreserveHostFlagWhenUpdatingUriWithoutHostDoesNotUpdateHostHeader()
    {
        $request = Request::createGet('')
            ->withAddedHeader('Host', 'example.com');

        $uri = uri();
        $new = $request->withUri($uri);

        $this->assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    public function testHostHeaderUpdatesToUriHostAndPortWhenPreserveHostDisabledAndNonStandardPort()
    {
        $request = Request::createGet('')
            ->withAddedHeader('Host', 'example.com');

        $uri = uri()
            ->withHost('www.example.com')
            ->withPort(10081);
        $new = $request->withUri($uri);

        $this->assertEquals('www.example.com:10081', $new->getHeaderLine('Host'));
    }

    public function headersWithInjectionVectors()
    {
        return [
            'name-with-cr'           => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'           => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'         => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'        => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr'          => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'          => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'        => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'       => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'    => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'    => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'  => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
        ];
    }

    /**
     * @dataProvider headersWithInjectionVectors
     */
    public function testConstructorRaisesExceptionForHeadersWithCRLFVectors($name, $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $request = Request::createGet(null, [$name => $value]);
    }
}
