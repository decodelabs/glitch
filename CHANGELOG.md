## v0.15.0 (2019-10-14)
* Added production mode error message page
* Added ability to gracefully handle parse errors
* Automatically detect text/plain header and switch renderer
* Bypass segfault with EventBase class
* Added contain() helper to Context
* Fully wrap ErrorException in error_handler()
* Updated docs

## v0.14.8 (2019-10-04)
* Fixed base renderDump in production mode
* Updated text icon rendering
* Updated string styling in lists
* Fixed whitespace in strings
* Updated array key colours
* Added parent namespace to class name if also interface
* Added scalarToString() for Reflection inspector

## v0.14.7 (2019-10-03)
* Added production render override option

## v0.14.6 (2019-10-03)
* Added emergency exception trace in production mode

## v0.14.5 (2019-10-03)
* Fixed identifier lists

## v0.14.4 (2019-10-03)
* Fixed identifier rendering

## v0.14.3 (2019-10-03)
* Updated text and definition rendering

## v0.14.2 (2019-10-02)
* Updated errorPageRenderer logic
* Make exception info section visible
* Removed r7 inspector
* Updated entity value handling

## v0.14.1 (2019-10-02)
* Improved HTTP header handling
* Added Packet class for Transport passthrough

## v0.14.0 (2019-10-01)
* Simplified dump interface
* Added header buffer sender mechanism

## v0.13.3 (2019-10-01)
* Fixed binary rendering

## v0.13.2 (2019-10-01)
* Simplified stat rendering
* Fixed stack trace rendering
* Improved production mode handling

## v0.13.1 (2019-09-30)
* Updated default styles

## v0.13.0 (2019-09-30)
* Converted styling to use root css variables

## v0.12.0 (2019-09-30)
* Add className display option to Entities
* Fixed PDO attribute error catching
* Added r7 dump support
* Included interfaces in dump inspector lookup stack
* Added dd2 test dump helper
* Removed dark mode switch
* Simplified source styling
* Moved source highlighter to Enlighten library

## v0.11.1 (2019-09-27)
* Added static glitch facade instantiation
* Added Carbon inspect support

## v0.11.0 (2019-09-13)
* Converted root class into Veneer Facade
* Simplified Context interface
* Updated to Veneer v0.3 structure
* Added various PHP extension inspectors

## v0.10.0 (2019-09-09)
* Removed bootstrap dependency
* Removed asset-packagist dependency
* Added output buffer detection and escaping
* Added PSR-3 Logger support
* Added responsive font scaling

## v0.9.0 (2019-09-06)
* Added exception handler hook
* Added exception dump interface
* Update standard dump interface to use full UI
* Added dark mode styling
* Improved \Glitch::incomplete() support
* Tidied up sass code

## v0.8.0 (2019-09-03)
* Split shared renderer functions to trait
* Added plain text renderer
* Added CLI renderer

## v0.7.0 (2019-09-01)
* Added Core, SPL and Date inspectors
* Fixed object reference ids
* Refactored package structure
* Improved various dump renderers
* Improved Factory CodeGen

## v0.6.0 (2019-08-23)
* Initial dump renderer support
* Refactored codebase
* Removed Symfony dependency
* Added transport library

## v0.5.0 (2019-08-16)
* Basic dynamic exception support
