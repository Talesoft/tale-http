<?php declare(strict_types=1);

namespace Tale\Http;

final class Method
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const HEAD = 'HEAD';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const TRACE = 'TRACE';
    public const OPTIONS = 'OPTIONS';
    public const CONNECT = 'CONNECT';

    private function __construct() {}
}