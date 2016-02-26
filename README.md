
# Tale HTTP
**A Tale Framework Component**

# What is Tale HTTP?

Tale HTTP is a set of HTTP utilities for PHP that follow the [PSR-7](http://www.php-fig.org/psr/psr-7/) standard.

The library and its modules are heavily influenced by [Zend Diactoros](https://github.com/zendframework/zend-diactoros) (Thank you guys, great work you did there!), while Tale HTTP has a different aim than the Zend version.

Tale HTTP aims to just be a small wrapper over the HTTP-environment of PHP and allows easy use of the PSR-7 implementation and the integration in many projects that come with it.

It's a one-liner to get Tale HTTP started and it has never been so easy to modify the actual HTTP output of your application


# Installation

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


# Usage

Define autoloader, load ServerRequest and you're ready to go

```php

include 'vendor/autoload.php';

$request = Http::getServerRequest();

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
    ->withBody(new StringStream('The page you tried to call doesn\'t exist!'));
    
Http::emit($response);
```

or automatically (this also invokes some request/response magic that automatically fixes response headers etc.)

```php

$json = json_encode($someData);

$response = $request->createResponse()
    ->withHeader('Content-Type', 'application/json')
    ->withBody(new StringStream($json));
    
    
//Output response to client
Http::emit($response);
```
