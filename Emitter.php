<?php

namespace Tale\Http;

use Psr\Http\Message\ResponseInterface;

final class Emitter
{

    public static function emit(ResponseInterface $response)
    {

        $initialHeaderLine = implode(' ', [
            'HTTP/'.$response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ]);

        header($initialHeaderLine, true, $response->getStatusCode());
        foreach ($response->getHeaders() as $name => $value) {

            header("$name: ".implode(',', $value));
        }

        echo (string)$response->getBody();
    }
}