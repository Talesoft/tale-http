<?php declare(strict_types=1);

namespace Tale\Http\ResponseEmitterInterface;

use Psr\Http\Message\ResponseInterface;

interface ResponseTrapInterface
{
    public function emit(ResponseInterface $response): void;
}