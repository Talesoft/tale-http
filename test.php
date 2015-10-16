<?php

use Tale\Http\ServerRequest,
    Tale\Http\StringStream;

include 'vendor/autoload.php';




$req = (new ServerRequest())
    ->withAttribute('testAttribute', 'test-value');







//All application logic goes here!!!









$response = $req->createResponse()
    ->withHeader('Content-Type', 'application/json')
    ->withBodyString(json_encode(['a' => 'b', 'c' => 'd']));


$response->emit();