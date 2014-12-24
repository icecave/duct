# Duct Changelog

### 2.0.0 (2014-12-24)

* **[BC]** Updated to [igorw/evenement](https://github.com/igorw/evenement) version 2 (affects public interface of `EventedParser`)
* **[BC]** Updated minimum PHP version requirement to 5.4
* **[BC]** Removed `AbstractParser` (added `ParserInterface`)
* **[IMPROVED]** Added `produceAssociativeArrays` constructor parameter to `Parser`

### 1.0.0 (2014-09-30)

* **[NEW]** Added support for representing JSON objects as associative arrays

### 0.4.0 (2014-02-07)

* **[BC]** Removed [icecave/collections](https://github.com/IcecaveStudios/collections) for performance reasons
* **[IMPROVED]** Updated autoloader to [PSR-4](http://www.php-fig.org/psr/psr-4/)

### 0.3.0 (2013-09-30)

* **[IMPROVED]** Improved handling of large JSON documents with `EventedParser`
* **[BC]** `EventedParser` no longer emits the `document` event, replaced with `document-open` and `document-close`

### 0.2.1 (2013-07-08)

* **[NEW]** Added `error` event to `EventedParser`
* **[FIXED]** Parsers are now reset when an exception is thrown

### 0.2.0 (2013-07-08)

* **[NEW]** Added `EventedParser`, a SAX-JS/Clarinet-like event-based JSON parser
* **[BC]** Moved `Lexer` and `TokenStreamParser` into `Detail` namespace - these classes are no longer part of the public API

### 0.1.0 (2013-05-02)

* Initial release
