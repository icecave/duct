# Duct

[![Build Status]](http://travis-ci.org/IcecaveStudios/duct)
[![Test Coverage]](http://icecavestudios.github.io/duct/artifacts/tests/coverage)

**Duct** is a PHP library for incrementally parsing continuous streams of JSON values.

**Duct** is designed to parse sequential JSON values from data streams, without framing or demarcation outside of the
JSON specification.

Please note that **Duct** does not current provide an evented (SAX-like) JSON parser, though there are plans to introduce
such functionality in the future.

* Install via [Composer](http://getcomposer.org) package [icecave/duct](https://packagist.org/packages/icecave/duct)
* Read the [API documentation](http://icecavestudios.github.io/duct/artifacts/documentation/api/)

## Example

```php
<?php
use Icecave\Duct\Parser;

$parser = new Parser;

// JSON data can be fed to the parser incrementally.
$parser->feed('[ 1, ');

// Completed values can be retreived using the values() method, which returns an
// Icecave\Collections\Vector of values.
//
// At this point no complete object has been parsed so the vector is empty.
$values = $parser->values();
assert($values->isEmpty());

// As more data is fed to the parser, we now have one value available, an array
// of elements 1, 2, 3.
//
// Note that calling values() is destructive, in that any complete objects are
// removed from the parser and will not be returned by future calls to values().
$parser->feed('2, 3 ][ 4, 5');
$values = $parser->values();
assert($values->size() === 1);
assert($values[0] == array(1, 2, 3));

// Finally we feed the remaining part of the second object to the parser and the
// second value becomes available.
$parser->feed(', 6 ]');
$values = $parser->values();
assert($values->size() === 1);
assert($values[0] == array(4, 5, 6));
```

<!-- references -->
[Build Status]: https://raw.github.com/IcecaveStudios/duct/gh-pages/artifacts/images/icecave/regular/build-status.png
[Test Coverage]: https://raw.github.com/IcecaveStudios/duct/gh-pages/artifacts/images/icecave/regular/coverage.png

