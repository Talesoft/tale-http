
[![Packagist](https://img.shields.io/packagist/v/talesoft/tale-http.svg?style=for-the-badge)](https://packagist.org/packages/talesoft/tale-http)
[![License](https://img.shields.io/github/license/Talesoft/tale-http.svg?style=for-the-badge)](https://github.com/Talesoft/tale-http/blob/master/LICENSE.md)
[![CI](https://img.shields.io/travis/Talesoft/tale-http.svg?style=for-the-badge)](https://travis-ci.org/Talesoft/tale-http)
[![Coverage](https://img.shields.io/codeclimate/coverage/Talesoft/tale-http.svg?style=for-the-badge)](https://codeclimate.com/github/Talesoft/tale-http)

Tale Http
=========

What is Tale Http?
------------------

Tale HTTP is an implementation of the PSR-7 and PSR-17 standards
that doesn't add any own great logic or functionality, but rather
acts as a base for sophisticated libraries that require a lightweight
default implementation.

The classes are laid out to be used inside dependency injection
containers following the PSR-11 spec.

There are no other hard dependencies on this library, not even
`Tale\Url` or `Tale\Stream`. Every single part of it is interoperable 
with any PSR-7/PSR-17 implementation through PSR factories.


Installation
------------

```bash
composer require talesoft/tale-http
```

Usage
-----

Basically, just use an auto-wiring DI container, add the
different factories of this libraries and then inject them.

Notice this is not a working example, just the plan.

```php
use Tale\Uri\Factory as UriFactory; //using talesoft/tale-uri
use Tale\Stream\Factory as StreamFactory; //using talesoft/tale-stream
use Tale\Http\Factory\RequestFactory;
use Tale\Http\Factory\ResponseFactory;
use Tale\Http\Factory\UploadedFileFactory;
use Tale\Http\Factory\GlobalServerRequestFactory;

$configurator = $container->get(ContainerConfigurator::class);
$configurator->setDefaultParameter('protocolVersion', '1.1');
$configurator->setDefaultParameter('headers', []);

$container->register(UriFactory::class);
$container->register(StreamFactory::class);
$container->register(RequestFactory::class);
$container->register(ResponseFactory::class);
$container->register(UploadedFileFactory::class);
```

Every single factory can be swapped out for another factory
following the PSR specs.

### GlobalServerRequestFactory

`use Tale\Http\Factory\GlobalServerRequestFactory;`

A factory for a server request object populated by PHP global
values. Intended use probably looks somewhat like this:

```php
//This will auto-wire with other required HTTP factories automatically
$factory = $container->get(GlobalServerRequestFactoryInterface::class);

//Build ServerRequest from globals
$serverRequest = $factory->createGlobalServerRequest();

//Add to DI container
$container->add($serverRequest);
```

and then, maybe some kind of base controller

```php
abstract class AbstractController
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;
    
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }
    
    //Utilities and stuff...
}
```

and then in a controller

```php
final class PostController extends AbstractController
{
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $page = (int)$request->getQueryParam('page', '0');
        
        //get page $page of $items
        return $this->render('some/view', [
            'items' => $items
        ]);
    }
}
```

### GlobalResponseTrap

`use Tale\Http\ResponseTrap\GlobalResponseTrap;`

The global response trap is meant as an easy way to get your
response out to the client, including headers, status codes etc.

Register GlobalResponseTrap in your DI container, from then on
it works somewhat like this:

```php
final class ResponseSubscriber implements EventSubscriberInterface
{
    /** @var ResponseTrapInterface */
    private $responseTrap;
    
    public function __construct(ResponseTrapInterface $responseTrap)
    {
        $this->responseTrap = $responseTrap;
    }
    
    public function onResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        
        $this->responseTrap->emit($response);
    }
    
    //...
}
```

### Q & A

**Q: Why are Request/Response parameters ordered like this and why don't 
they have default values?**

A: So that they are required to fully leverage the factories and DI auto-wiring. 
The body can't be an optional value or it would require StreamFactory injections
and this way it's easier to e.g. configure all elements to use
HTTP 1.0 or 2.0 instead of 1.1

TODO: Much documentation...