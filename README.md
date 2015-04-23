# Duct

[![Build Status]](https://travis-ci.org/IcecaveStudios/duct)
[![Test Coverage]](https://coveralls.io/r/IcecaveStudios/duct?branch=develop)
[![SemVer]](http://semver.org)

**Duct** is a PHP library for incrementally parsing continuous streams of JSON values.

* Install via [Composer](http://getcomposer.org) package [icecave/duct](https://packagist.org/packages/icecave/duct)
* Read the [API documentation](http://icecavestudios.github.io/duct/artifacts/documentation/api/)

**Duct** is designed to parse sequential JSON values from data streams, without framing or demarcation outside of the
JSON specification.

## Examples

### Simple parsing

**Duct** can be used to parse multiple JSON documents in a single call to `Parser::parse()`.
The JSON string given must contain complete values.

```php
use Icecave\Duct\Parser;

$parser = new Parser;
$values = $parser->parse('[ 1, 2, 3 ] [ 4, 5, 6 ]');

assert($values[0] === [1, 2, 3]);
assert($values[1] === [4, 5, 6]);
```

### Incremental parsing

Asynchronous, incremental parsing is also possible using the `Parser::feed()`, `values()` and `finalize()` methods.

```php
use Icecave\Duct\Parser;

$parser = new Parser;

// JSON data can be fed to the parser incrementally.
$parser->feed('[ 1, ');

// An array of completed values can be retreived using the values() method.
// At this point no complete object has been parsed so the array is empty.
$values = $parser->values();
assert(0 === count($values));

// As more data is fed to the parser, we now have one value available, an array
// of elements 1, 2, 3.
$parser->feed('2, 3 ][ 4, 5');
$values = $parser->values();
assert(1 === count($values));
assert($values[0] == [1, 2, 3]);

// Note that calling values() is destructive, in that any complete objects are
// removed from the parser and will not be returned by future calls to values().
$values = $parser->values();
assert(0 === count($values));

// Finally we feed the remaining part of the second object to the parser and the
// second value becomes available.
$parser->feed(', 6 ]');
$values = $parser->values();
assert(1 === count($values));
assert($values[0] == [4, 5, 6]);

// At the end of the JSON stream, finalize is called to parse any data remaining
// in the buffer. An exception is thrown if the buffer contains an incomplete
// value.
$parser->finalize();

// In this case there were no additional values.
$values = $parser->values();
assert(0 === count($values));
```

### Event-based parsing

**Duct** also provides `EventedParser`, an event-based incremental parser similar to the [Clarinet](https://github.com/dscape/clarinet)
library for JavaScript. Event management is provided by [Événement](https://github.com/igorw/evenement), a popular PHP
event library.

As per the example above the `feed()` and `finalize()` methods are used, however there is no `values()` method. Instead,
the following events are emitted as the buffer is parsed.

 * **document-open**: emitted when a JSON document is begun
 * **document-close**: emitted after an entire JSON document has been parsed
 * **array-open**: emitted when an array open bracket is encountered
 * **array-close**: emitted when an array closing bracket is encountered
 * **object-open**: emitted when an object open brace is encountered
 * **object-close**: emitted when an object closing brace is encountered
 * **object-key** (string $key): emitted when an object key is encountered
 * **value** (mixed $value): emitted whenever a scalar or null is encountered, including inside objects and arrays
 * **error** (Exception $error): emitted when a syntax error is encountered

## Contact us

* Follow [@IcecaveStudios](https://twitter.com/IcecaveStudios) on Twitter
* Visit the [Icecave Studios website](http://icecave.com.au)
* Join `#icecave` on [irc.freenode.net](http://webchat.freenode.net?channels=icecave)

<!-- references -->
[Build Status]: http://img.shields.io/travis/IcecaveStudios/duct/develop.svg?style=flat-square
[Test Coverage]: http://img.shields.io/coveralls/IcecaveStudios/duct/develop.svg?style=flat-square
[SemVer]: http://img.shields.io/:semver-2.0.2-brightgreen.svg?style=flat-square
