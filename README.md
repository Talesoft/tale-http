
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

Every single part of the library is interoperable with any PSR-7/PSR-17 
implementation through PSR factories.


Installation
------------

```bash
composer require talesoft/tale-http
```

Usage
-----

For now, check out the source code please.

All functionality is covered in `src/functions.php`.

Every single factory can be swapped out for another factory
following the PSR specs.

TODO: Much documentation...