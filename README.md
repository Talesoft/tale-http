
# Tale HTTP
### A PSR-7 compatible HTTP abstraction library for PHP

## What is Tale HTTP?

Tale HTTP is a set of HTTP utilities for PHP that follow the [PSR-7](http://www.php-fig.org/psr/psr-7/) standard.

The library and its modules are heavily influenced by [Zend Diactoros](https://github.com/zendframework/zend-diactoros) (Thank you guys, great work you did there!), while Tale HTTP has a different aim than the Zend version.

Tale HTTP aims to just be a small wrapper over the HTTP-environment of PHP and allows easy use of the PSR-7 implementation and the integration in many projects that come with it.

It's a one-liner to get Tale HTTP started and it has never been so easy to modify the actual HTTP output of your application


## Installation

Install via Composer

```bash
composer require "talesoft/tale-http:*"
composer install
```

or add it to your `package.json`

```json
{
    "require": {
        "talesoft/tale-http": ">=1.0"
    }
}
```


## Usage

Define autoloader, load ServerRequest and you're ready to go

```php

include 'vendor/autoload.php';

$request = new Tale\Http\ServerRequest();

//Print the exact same request the user sent to access this site in text form
echo $request;

//Get the current FULL URI
echo $request->getUri();

//Get the current request path
echo $request->getUri()->getPath();


//Handle uploaded files, maybe?
if ($request->isPost()) {
    foreach ($request->getUploadedFiles() as $i => $file) {
    
        $file->moveTo("uploads/$userDir/upload-$i.ul");
        echo "Uploaded file ".$file->getClientName()."<br>";
    }
}
```

To build a neat HTTP response with the ability to append headers etc., use the Response class

Either manually
```php

$response = (new Response(404))
    ->withBodyString('The page you tried to call doesn\'t exist!');
    
$response->emit();
```

or automatically (this also invokes some request/response magic that automatically fixes response headers etc.)

```php

$json = json_encode($someData);

$response = $request->createResponse()
    ->withHeader('Content-Type', 'application/json')
    ->withBodyString($json);
    
    
//Output response to client
$response->emit();
```


## Automatic SAPI (PHP's Server API) handling

As soon as the `ServerRequest` realizes that you're in a CLI-application, it will try some conversions to keep your application running smoothly.

This involves taking over Command Line Arguments to `$_GET`-arguments

You typically get `GET`-arguments (Query String values) via the `getQueryParams`-method of the `ServerRequest`

```php

//GET /index.php?controller=user&action=delete&id=15

$args = $request->getQueryParams();

var_dump($args); //['controller' => 'user', 'action' => 'delete', 'id' => 15]
```

Now when you call the app via the CLI, PHP usually has no method of obtaining the Query Paramaters you'd like to use (and used), so it will probably do nothing or run into errors.

With Tale HTTP you can just pass those query arguments as typical Command Line Arguments and get the correct results.

```bash
$ php index.php --controller user --action delete --id 15
```

and you'll get the same results as in the example above.

You might also pass a path as the first argument that get's noticed as the `REQUEST_URI` you call your page with

```bash
$ php index.php /user/delete/15
```

and your router can handle this stuff by itself (You can get this path via `$request->getUri()->getPath()` instantly.

