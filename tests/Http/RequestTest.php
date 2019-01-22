<?php
declare(strict_types=1);

namespace Tale\Test\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Tale\Http\Method;
use Tale\Http\Request;
use Tale\Stream\MemoryStream;
use Tale\Uri\Factory;

class RequestTest extends TestCase
{
    /** @var Request */
    private $request;

    /** @var UriFactoryInterface */
    private $uriFactory;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->uriFactory = new Factory();
    }

    public function setUp()
    {
        $this->request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri(), null, new MemoryStream());
    }

    public function testMethodIsGetByDefault()
    {
        $this->assertEquals(Method::GET, $this->request->getMethod());
    }

    public function testMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request = $this->request->withMethod('POST');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals(Method::POST, $request->getMethod());
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
        $request = $this->request->withUri($this->uriFactory->createUri('https://example.com:10082/foo/bar?baz=bat'));
        $this->assertNotSame($this->request, $request);
        $request2 = $request->withUri($this->uriFactory->createUri('/baz/bat?foo=bar'));
        $this->assertNotSame($this->request, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertEquals('/baz/bat?foo=bar', (string)$request2->getUri());
    }

    public function testConstructorCanAcceptAllMessageParts()
    {
        $uri     = $this->uriFactory->createUri('http://example.com/');
        $body    = new MemoryStream();
        $headers = [
            'x-foo' => ['bar'],
        ];
        $request = new Request('1.1', $headers, Method::POST, $uri, null, $body);

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
        $request = $this->request->withUri($this->uriFactory->createUri('http://example.com'));
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function requestsWithUri()
    {
        return [
            'absolute-uri' => [
                new Request('1.1', [], Method::POST, $this->uriFactory->createUri('https://api.example.com/user'), null, new MemoryStream()),
                '/user'
            ],
            'absolute-uri-with-query' => [
                new Request('1.1', [], Method::POST, $this->uriFactory->createUri('https://api.example.com/user?foo=bar'), null, new MemoryStream()),
                '/user?foo=bar'
            ],
            'relative-uri' => [
                new Request('1.1', [], Method::GET, $this->uriFactory->createUri('/user'), null, new MemoryStream()),
                '/user'
            ],
            'relative-uri-with-query' => [
                new Request('1.1', [], Method::GET, $this->uriFactory->createUri('/user?foo=bar'), null, new MemoryStream()),
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
        $request = $this->request->withUri($this->uriFactory->createUri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri($this->uriFactory->createUri('http://mwop.net/bar/baz'));
        $this->assertNotEquals($original, $newRequest->getRequestTarget());
    }

    public function testGetHeadersContainsHostHeaderIfUriWithHostIsPresent()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri('http://example.com'), null, new MemoryStream());
        $headers = $request->getHeaders();
        $this->assertArrayHasKey('Host', $headers);
        $this->assertContains('example.com', $headers['Host']);
    }

    public function testGetHeadersContainsNoHostHeaderIfNoUriPresent()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri(''), null, new MemoryStream());
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHeadersContainsNoHostHeaderIfUriDoesNotContainHost()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri(''), null, new MemoryStream());
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHostHeaderReturnsUriHostWhenPresent()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri('http://example.com'), null, new MemoryStream());
        $header = $request->getHeader('host');
        $this->assertEquals(['example.com'], $header);
    }

    public function testGetHostHeaderReturnsEmptyArrayIfNoUriPresent()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri(''), null, new MemoryStream());
        $this->assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderReturnsEmptyArrayIfUriDoesNotContainHost()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri(''), null, new MemoryStream());
        $this->assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderLineReturnsUriHostWhenPresent()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri('http://example.com'), null, new MemoryStream());
        $header = $request->getHeaderLine('host');
        $this->assertContains('example.com', $header);
    }

    public function testGetHostHeaderLineIsEmptyIfNoUriPresent()
    {
        $request = new Request('1.1', [], Method::GET, $this->uriFactory->createUri(), null, new MemoryStream());
        $this->assertEmpty($request->getHeaderLine('host'));
    }

    public function testPassingPreserveHostFlagWhenUpdatingUriDoesNotUpdateHostHeader()
    {
        $request = (new Request('1.1', [], Method::GET, $this->uriFactory->createUri(), null, new MemoryStream()))
            ->withAddedHeader('Host', 'example.com');

        $uri = ($this->uriFactory->createUri())->withHost('www.example.com');
        $new = $request->withUri($uri, true);

        $this->assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    public function testNotPassingPreserveHostFlagWhenUpdatingUriWithoutHostDoesNotUpdateHostHeader()
    {
        $request = (new Request('1.1', [], Method::GET, $this->uriFactory->createUri(), null, new MemoryStream()))
            ->withAddedHeader('Host', 'example.com');

        $uri = $this->uriFactory->createUri();
        $new = $request->withUri($uri);

        $this->assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    public function testHostHeaderUpdatesToUriHostAndPortWhenPreserveHostDisabledAndNonStandardPort()
    {
        $request = (new Request('1.1', [], Method::GET, $this->uriFactory->createUri(), null, new MemoryStream()))
            ->withAddedHeader('Host', 'example.com');

        $uri = ($this->uriFactory->createUri())
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
        $request = new Request('1.1', [$name =>  $value], Method::GET, $this->uriFactory->createUri(), null, new MemoryStream());
    }
}