## v0.19.4 (2025-03-03)
* Fixed ErrorPageRenderer null access

## v0.19.3 (2025-02-20)
* Upgraded Coercion dependency

## v0.19.2 (2025-02-19)
* Improved super-early stage environment
* Improved callable handling

## v0.19.1 (2025-02-18)
* Removed CHANGELOG.md from export-ignore

## v0.19.0 (2025-02-13)
* Added support for lazy objects
* Updated Stack Frame handling
* Fixed extended features in Reflection rendering
* Tidied boolean logic
* Fixed Exceptional syntax
* Upgraded PHPStan to v2
* Added PHP8.4 to CI workflow
* Made PHP8.4 minimum version

## v0.18.20 (2025-02-07)
* Removed ref to E_STRICT

## v0.18.19 (2025-02-07)
* Fixed implicit nullable arguments

## v0.18.18 (2025-02-04)
* Updated Exceptional call signature
* Restructured SASS styling
* Upgraded Vite to v6

## v0.18.17 (2024-08-21)
* Converted consts to PascalCase
* Made renderer consts protected
* Improved Veneer support

## v0.18.16 (2024-07-17)
* Updated Veneer dependency

## v0.18.15 (2024-06-19)
* Custom rendering for SensitiveParameterValue
* Added support for sensitive properties

## v0.18.14 (2024-04-29)
* Removed jQuery package
* Fixed Zest editor config

## v0.18.13 (2024-04-29)
* Converted theme to Vite

## v0.18.12 (2024-04-29)
* Updated dependency list
* Fixed Veneer stubs in gitattributes

## v0.18.11 (2023-12-12)
* Added CORS headers to HTTP transport
* Added never return type to dd()

## v0.18.10 (2023-11-18)
* Improved DateTimeInterface support
* Added enum dump support
* Added Fiber dump support
* Made PHP8.1 minimum version

## v0.18.9 (2023-10-30)
* Read unkeyed meta from glitchDump as list

## v0.18.8 (2023-09-29)
* Avoid throwing non-critical ErrorException in production

## v0.18.7 (2023-09-26)
* Converted phpstan doc comments to generic

## v0.18.6 (2023-01-06)
* Disabled object auto-count in dump
* Migrated to use effigy in CI workflow
* Fixed PHP8.1 testing

## v0.18.5 (2022-10-03)
* Fixed exact-match path normalization

## v0.18.4 (2022-09-27)
* Updated Veneer stub
* Updated composer check script

## v0.18.3 (2022-09-27)
* Updated Veneer dependency
* Check if dump properties are initialized

## v0.18.2 (2022-09-15)
* Added ProductionOverride to Renderer interface
* Updated CI environment

## v0.18.1 (2022-08-24)
* Added concrete types to all members

## v0.18.0 (2022-08-23)
* Removed PHP7 compatibility
* Updated PSR Log interface to v3
* Updated ECS to v11
* Updated PHPUnit to v9

## v0.17.7 (2022-03-10)
* Transitioned from Travis to GHA
* Updated PHPStan and ECS dependencies

## v0.17.6 (2021-10-20)
* Updated Veneer dependency
* Fixed Spl inspect PHPStan type check

## v0.17.5 (2021-05-11)
* Added Veneer IDE support stub

## v0.17.4 (2021-05-01)
* Improved return type hints

## v0.17.3 (2021-04-16)
* Fixed dump stack rewind handling

## v0.17.2 (2021-04-07)
* Updated for full PHPStan conformance

## v0.17.1 (2021-04-02)
* Fixed error handler error_reporting level check
* Add ReflectionUnionType support

## v0.17.0 (2021-03-18)
* Enabled PHP8 testing
* Fixed and updated Symfony var-dumper support
* Applied full PSR12 standards
* Added PSR12 check to Travis build

## v0.16.5 (2020-10-05)
* Updated Veneer and PHPStan

## v0.16.4 (2020-10-04)
* Upgraded to Veneer 0.6

## v0.16.3 (2020-10-04)
* Switched to Glitch Proxy incomplete()

## v0.16.2 (2020-10-02)
* Use Exceptional for incomplete()

## v0.16.1 (2020-10-02)
* Updated dependencies

## v0.16.0 (2020-10-02)
* Moved exception Factory to Exceptional
* Removed all Exception generation functionality

## v0.15.10 (2020-09-30)
* Switched to Exceptional for exception generation

## v0.15.9 (2020-09-29)
* Reverted to PHPUnit 8

## v0.15.8 (2020-09-29)
* Moved Stack and Dumpable to glitch-support

## v0.15.7 (2020-09-29)
* Only wrap HTML render in iframe if dumped in buffer
* Updated project description

## v0.15.6 (2020-09-25)
* Added Dumpable interface and inspector support
* Converted EGlitch to use Dumpable interface

## v0.15.5 (2020-09-24)
* Updated Composer dependency handling

## v0.15.4 (2019-11-07)
* Removed need for Inspectable interface to provide custom dumping

## v0.15.3 (2019-11-06)
* Improved CLI exception dump consistency
* Added useTextRenderer() shortcut to Context
* Removed includes from environment list
* Improved globals handling in environment
* Fixed open / close transition animation
* Added json serialize support to stack trace / frame
* Updated XMLWriter dumper
* Updated Veneer dependency

## v0.15.2 (2019-10-26)
* Added log listener callback
* Added ESetup glitch type
* Fixed interface inheritance bug in Glitch Factory
* Improved path normalizer
* Improved PHPStan setup

## v0.15.1 (2019-10-16)
* Added PHPStan support
* Bugfixes and updates from max level PHPStan scan

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
