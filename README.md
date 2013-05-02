# Duct

[![Build Status]](http://travis-ci.org/IcecaveStudios/duct)
[![Test Coverage]](http://icecave.com.au/duct/artifacts/tests/coverage)

**Duct** is a PHP library for parsing continuous streams of JSON values.

## Installation

* [Composer](http://getcomposer.org) package [icecave/duct](https://packagist.org/packages/icecave/duct)

## Example

```php
<?php
use Icecave\Duct\Parser;

$parser = new Parser;

// JSON data can be fed to the parser incrementally ...
$parser->feed('[ 1, ');
$parser->feed('2, 3');
$parser->feed('][ 4, 5, 6 ]');

// Any completed values can be retrieved from the parser at any time ...
$result = $parser->values();
assert($result[0] == array(1, 2, 3));
assert($result[1] == array(4, 5, 6));
```

<!-- references -->
[Build Status]: https://raw.github.com/IcecaveStudios/duct/gh-pages/artifacts/images/icecave/regular/build-status.png
[Test Coverage]: https://raw.github.com/IcecaveStudios/duct/gh-pages/artifacts/images/icecave/regular/coverage.png
