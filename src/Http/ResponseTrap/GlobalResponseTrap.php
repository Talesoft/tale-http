<?php declare(strict_types=1);

namespace Tale\Http\ResponseTrap;

use Psr\Http\Message\ResponseInterface;
use Tale\Http\ResponseTrapInterface;

final class GlobalResponseTrap implements ResponseTrapInterface
{
    public function emit(ResponseInterface $response): void
    {
        if (function_exists('headers_sent') && headers_sent()) {
            throw new \RuntimeException(
                'Failed to emit response: HTTP headers have already been sent. '.
                'There is probably accidental output somewhere in your code.'
            );
        }

        $initialHeaderLine = implode(' ', [
            'HTTP/'.$response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ]);

        header($initialHeaderLine, true, $response->getStatusCode());
        foreach ($response->getHeaders() as $name => $value) {
            header("$name: ".implode(',', $value));
        }
        $bodyStream = $response->getBody()->detach();
        fpassthru($bodyStream);
        fclose($bodyStream);
    }
}