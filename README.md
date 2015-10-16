
# Tale HTTP
### A PSR-7 compatible HTTP abstraction library for PHP

## What is Tale HTTP?

Tale HTTP is a set of HTTP utilities for PHP that follow the [PSR-7](http://www.php-fig.org/psr/psr-7/) standard.

The library and its modules are heavily influenced by [Zend Diactoros](https://github.com/zendframework/zend-diactoros) (Thank you guys, great work you did there!), while Tale HTTP has a different aim than the Zend version.

Tale HTTP aims to just be a small wrapper over the HTTP-environment of PHP and allow for easy use of the PSR-7 implementation and the integration in many projects that come with it.




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

//Print the exact same request the user sent to access this site
echo $request;

//Get the current FULL Url
echo $request->getUrl();

//Get the current request path
echo $request->getUrl()->getPath();


//Handle uploaded files, maybe?
if ($request->isPost()) {
    foreach ($request->getUploadedFiles() as $i => $file) {
    
        $file->moveTo("uploads/$userDir/upload-$i.ul");
        echo "Uploaded file ".$file->getClientName()."<br>";
    }
}
```

To build a neat HTTP response with the ability to append headers etc., use the Response class
in conjunction with the PHP Output Stream