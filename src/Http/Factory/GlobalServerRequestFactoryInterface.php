<?php declare(strict_types=1);

namespace Tale\Http\Factory;

use Psr\Http\Message\ServerRequestInterface;

interface GlobalServerRequestFactoryInterface
{
    public function createGlobalServerRequest(): ServerRequestInterface;
}