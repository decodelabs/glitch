# Glitch

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/glitch?style=flat)](https://packagist.org/packages/decodelabs/glitch)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/glitch.svg?style=flat)](https://packagist.org/packages/decodelabs/glitch)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/glitch.svg?style=flat)](https://packagist.org/packages/decodelabs/glitch)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/glitch/integrate.yml?branch=develop)](https://github.com/decodelabs/glitch/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/glitch?style=flat)](https://packagist.org/packages/decodelabs/glitch)

### Better tools for when things go wrong

Glitch is a standalone PHP package designed to dramatically improve error handling and inspection when developing your applications.

The project aims to provide deep data inspection tools and an Exception handling interface.

---

![v0.19 interface](docs/v0.19.png)


## Installation
Glitch should be installed via composer

```bash
composer require decodelabs/glitch
```

## Usage

Glitch uses [Veneer](https://github.com/decodelabs/veneer) with its frontage registered at `DecodeLabs\\Glitch`.
You can access all of the primary functionality through this frontage:

```php
use DecodeLabs\Glitch;

Glitch::dump('hello');
```

### Setup

Otherwise, Glitch works out of the box without any special setup.
There are however some optional steps you can take to customise operation.


Register as the default error handler:

```php
use DecodeLabs\Glitch;

Glitch::registerAsErrorHandler();
```

Pass the `microtime()` of initial app launch for timing purposes:

```php
use DecodeLabs\Glitch;

Glitch::setStartTime(microtime(true));
```

## Dumps
Dump anything and everything easily, using simple global functions.
The functions mirror those used in Symfony/VarDumper, maintaining compatibility by using Symfony's VarDumper interface if it is already loaded.

```php
use DecodeLabs\Glitch;

class MyThing {}
$myObject = new MyThing();

// This will dump the object and carry on
dump($myObject);

// This will dump the object and exit
dd($myObject);
```

You can also mark functions as incomplete whilst in development:
```php
use DecodeLabs\Glitch;

function myFunction() {
    // This will throw a Glitch exception
    Glitch::incomplete([
        'info' => 'some test info'
    ]);
}
```

#### Renderers
The resulting dump UI (when using the HTML renderer, the default option) is injected into an iframe at runtime so can be rendered into any HTML page without breaking anything. If the page is otherwise empty, the iframe will expand to fill the viewport if possible.

The dump output is rendered by an instance of `DecodeLabs\Glitch\Renderer` which can be overridden on the default `Context` at startup. The `Html` renderer is loaded under http sapi, the `Cli` renderer is used when under the CLI sapi.

Custom renderers may convert `Entities` to other output formats depending on where they should be sent, such as Xml or Json for example. The Renderer system uses [Nuance](https://github.com/decodelabs/nuance) to inspect and render the data, please see that project for more information on how to create custom renderers.

#### Custom colours
The HTML renderer uses css variables to style individual element colours and can be overridden with custom values.
Create a custom css file with variable overrides:

```css
:root {
    --string: purple;
    --binary: green;
}
```

See [colours.scss](./zest/src/sass/global/_colors.scss) for all of the current colour override options.

Then load the file into the HTML renderer:

```php
use DecodeLabs\Glitch;

Glitch::getRenderer()->setCustomCssFile('path/to/my/file.css');
```

#### Transports
Once rendered, the dump information is delivered via an instance of `DecodeLabs\Glitch\Transport`, also overridable on the default `Context`. It is the responsibility of the `Transport` to deliver the rendered dump.

By default, the render is just echoed out to `STDOUT`, however custom transports may send information to other interfaces, browser extensions, logging systems, etc.


### Custom dumps
You can customise how your own class instances are dumped by implementing `DecodeLabs\Nuance\Dumpable` interface.

Please see the [Nuance documentation](https://github.com/decodelabs/nuance?tab=readme-ov-file#custom-dumps) for more information on how to do this.


## Licensing
Glitch is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
