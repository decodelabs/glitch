# Glitch
### Better exceptions for PHP.

Glitch is a standalone PHP package designed to improve end-to-end error handling and inspection when developing your applications.

The project aims to provide a radically enhanced Exception framework that decouples the _meaning_ of an Exception from the underlying _implementation_ functionality, alongside deep data inspection tools and an Exception handling interface.


## Installation
Glitch should be installed via composer

```bash
composer require decodelabs/glitch
```


### Setup
Register base paths for easier reading of file names:

```php
\Glitch\Context::getDefault()->registerPathAlias('app', '/path/to/my/app');

/*
/path/to/my/app/models/MyModel.php

becomes

app://models/MyModel.php
*/
```

Pass the <code>microtime()</code> of initial app launch if necessary:

```php
$time = microtime(true);
\Glitch\Context::getDefault()->setStartTime($time);
```


## Dumps
Dump anything and everything easily, using simple global functions.
The functions mirror those used in Symfony/VarDumper, maintaining compatibility by using Symfony's VarDumper interface if it is already loaded.

```php
class MyThing {}
$myObject = new MyThing();

// This will dump the object and carry on
dump($myObject);

// This will dump the object and exit
dd($myObject);
```

The resulting dump interface (when using the HTML renderer, the default option) is injected into an iframe at runtime so can be rendered into any HTML page without breaking anything. If the page is otherwise empty, the iframe will expand to fill the viewport if possible.

#### Renderers
The dump package is rendered by an instance of <code>DecodeLabs\Glitch\Renderer</code> which can be overridden on the default <code>Context</code> at startup. By default the <code>Html</code> renderer is loaded under normal circumstances, the <code>Cli</code> renderer is used when under the cli sapi.

Custom renderers may convert <code>Entities</code> to other formats such as Xml for example.

#### Transports
Once rendered, the package is delivered via an instance of <code>DecodeLabs\Glitch\Transport</code>, also overridable on the default <code>Context</code>. It is the responsibility of the <code>Transport</code> to deliver the rendered dump. By default, the render is just echoed out to <code>STDOUT</code>, however custom transports may send information to other interfaces, browser extensions, logging systems, etc.


## Exceptions
Throw <code>Glitches</code> rather than <code>Exceptions</code>, passing interface names to be mixed in as the method name (custom generated error interfaces must be prefixed with E) to the Glitch call.

```php
throw \Glitch::EOutOfBounds('This is out of bounds');

// Implement multiple interfaces
throw \Glitch::{'ENotFound,EBadMethodCall'}(
    "Didn't find a thing, couldn't call the other thing"
);

// You can associate a http code too..
throw \Glitch::ECompletelyMadeUpMeaning('My message', [
    'code' => 1234,
    'http' => 501
]);

// Implement already existing Exception interfaces
throw \Glitch::{'EInvalidArgument,Psr\\Cache\\InvalidArgumentException'}(
    'Cache items must implement Cache\\IItem',
    ['http' => 500],  // params
    $item             // data
);

// Reference interfaces using a path style
throw \Glitch::{'../OtherNamespace/OtherInterface'}('My exception');
```

Catch a Glitch in the normal way using whichever scope you require:

```php
try {
    throw \Glitch::{'ENotFound,EBadMethodCall'}(
        "Didn't find a thing, couldn't call the other thing"
    );
} catch(\EGlitch | \ENotFound | MyLibrary\EGlitch | MyLibrary\AThingThatDoesStuff\EBadMethodCall $e) {
    // All these types will catch
    dd($e);
}
```


### Traits

Custom functionality can be mixed in to the generated Glitch automatically by defining traits at the same namespace level as any of the interfaces being implemented.

```php
namespace MyLibrary;

trait EBadThingTrait {

    public function getCustomData(): ?string {
        return $this->params['customData'] ?? null;
    }
}

class Thing {
    public function doAThing() {
        throw \Glitch::EBadThing('A bad thing happened', [
            'customData' => 'My custom info'
        ]);
    }
}
```



## Other information
[Rationale for Glitch Exceptions](docs/Rationale.md)


## Licensing
Glitch is licensed under the MIT License. See [LICENSE](https://github.com/decodelabs/glitch/blob/master/LICENSE) for the full license text.
