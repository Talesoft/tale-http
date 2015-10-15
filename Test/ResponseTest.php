<?php
namespace Tale\Http\Test;

use Tale\Http\Response;
use Tale\Http\StatusCode;
use Tale\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;

class ResponseTest extends TestCase
{

    /** @var Response */
    protected $response;

    public function setUp()
    {
        $this->response = new Response();
    }

    public function testStatusCodeIs200ByDefault()
    {
        $this->assertEquals(StatusCode::OK, $this->response->getStatusCode());
    }

    public function testStatusCodeMutatorReturnsCloneWithChanges()
    {
        $response = $this->response->withStatus(400);
        $this->assertNotSame($this->response, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function invalidStatusCodes()
    {
        return [
            'too-low' => [99],
            'too-high' => [600],
            'null' => [null],
            'bool' => [true],
            'string' => ['foo'],
            'array' => [[200]],
            'object' => [(object) [200]],
        ];
    }

    /**
     * @dataProvider invalidStatusCodes
     */
    public function testCannotSetInvalidStatusCode($code)
    {
        $this->setExpectedException('InvalidArgumentException');
        $response = $this->response->withStatus($code);
    }

    public function testReasonPhraseDefaultsToStandards()
    {
        $response = $this->response->withStatus(422);
        $this->assertEquals('Unprocessable Entity', $response->getReasonPhrase());
    }

    public function testCanSetCustomReasonPhrase()
    {
        $response = $this->response->withStatus(422, 'Foo Bar!');
        $this->assertEquals('Foo Bar!', $response->getReasonPhrase());
    }

    public function testConstructorCanAcceptAllMessageParts()
    {
        $body = Stream::createMemory();
        $status = 302;
        $headers = [
            'location' => [ 'http://example.com/' ],
        ];

        $response = new Response($body, $status, $headers);
        $this->assertSame($body, $response->getBody());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($headers, $response->getHeaders());
    }

    public function invalidStatus()
    {
        return [
            'float' => [ 100.1 ],
            'bad-string' => [ 'Two hundred' ],
            'array' => [ [ 200 ] ],
            'object' => [ (object) [ 'statusCode' => 200 ] ],
            'too-small' => [ 1 ],
            'too-big' => [ 600 ],
        ];
    }

    /**
     * @dataProvider invalidStatus
     */
    public function testConstructorRaisesExceptionForInvalidStatus($code)
    {
        $this->setExpectedException('InvalidArgumentException', 'StatusCode needs');
        new Response(null, $code);
    }

    /**
     * @dataProvider invalidResponseHeader
     */
    public function testConstructorRaisesExceptionForInvalidHeader($name, $value)
    {
        $this->setExpectedException('InvalidArgumentException');
        new Response(null, null, [$name, $value]);
    }

    public function invalidResponseHeader()
    {

        return [
            'object' => ['x-object', (object)['invalid']],
            'int' => ['x-int', 15],
            'true' => ['x-true', true],
            'null' => ['x-null', null],
            'null-second' => ['x-null', ['some string', null]]
        ];
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
        $this->setExpectedException('InvalidArgumentException');
        $request = new Response(null, 200, [$name =>  $value]);
    }

    public function testResponseConvertsToStringProperly()
    {

        $body = Stream::createMemory('rb+');
        $body->write('This is my response body!');

        $expected = "HTTP/1.1 404 Not Found\r\nX-Test: test-value\r\nX-Test-2: test-1,test-2\r\n\r\nThis is my response body!";

        $responseText = (string)((new Response())
            ->withBody($body)
            ->withHeader('X-Test', 'test-value')
            ->withHeader('X-Test-2', ['test-1', 'test-2'])
            ->withStatus(StatusCode::NOT_FOUND)
        );

        $this->assertEquals($expected, $responseText);
    }
}