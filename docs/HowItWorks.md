# How the Glitch interface works

The Glitch Exception structure's main aim is to generate dynamic Exceptions based on a set of criteria in any particular context, with minimum boilerplate code.

## Calling the exception generator

Glitch combines a number of techniques to create a predictable and easy to use interface to the Exception generator mechanism.
It is made of three essential parts:

- A [Veneer Facade](https://github.com/decodelabs/veneer) that acts as a static reference to the active Context instance
- A <code>\__call()</code> method in the main Context class
- A global function in the root namespace for calls to Glitch without a type

### Veneer
More information about how Veneer works can be found in the [documentation for that project](https://github.com/decodelabs/veneer), however the main principle is for the library to generate a private static class that can act as a proxy to an instance of an object, and then automatically alias that private static class into the namespace you are working in, using <code>class_alias()</code>.

This allows you as a developer to reference the functionality in the target object instance by calling the equivalent _static_ methods on the alias, without even having to define a <code>use</code> statement in the header of your file.

Glitch registers <code>DecodeLabs\Glitch\Context</code> as the target object to be referenced under the Facade named "Glitch"; calling a static method on the <code>Glitch</code> class in _any_ namespace passes the call through to the Context object.

```php
namespace Any\Old\Namespace;

Glitch::setRunMode('production');

// is directly equivalent to

$glitchContext = $psr11Container->get('glitch');
$glitchContext->setRunMode('production');
```

Having this Facade available effectively allows you as a developer to reference Glitch in the most straightforward way possible.


### Using dynamic method names as parameters
Glitch then utilizes an aspect of PHP that allows method names to be used as arbitrary parameters: <code>\__call()</code> and <code>\__callStatic()</code>.

The main Context class contains a <code>\__call()</code> magic method that captures all method calls on the object for which a function has not been defined. Due to the dynamic nature of PHP, this can include arbitrary strings:

```php
$object->{'abitrary method name'}();
```

Glitch uses this feature as a means of passing through the projected _type_ of exception to be generated, and parses that method name out to expand commas into an array:

```php
throw $glitchContext->{'ERuntime,ENotFound'}('message');

// internally:
// $types = ['ERuntime', 'ENotFound'];
```

The Veneer Facade then acts as a go-between allowing _static_ invocation of this function using <code>\__callStatic()</code> in exactly the same way:

```php
throw Glitch::{'ERuntime,ENotFound'}('message');
```

#### Global function

The global Glitch function in the root namespace acts as a fallback for when you may want to generate a generic EGlitch exception without any other types defined:

```php
throw Glitch('message');
```


## Calling the factory
Once the projected exception types have been captured by one of the <code>\__call()</code> magic methods, they are then passed with the message and exception parameters to an Exception Factory.

It is the sole responsibility of the Factory to actually generate an instance of an Exception for the calling code to throw.

It uses a combination of <code>eval()</code> and anonymous classes to build a custom class specific to the current context containing a mix of interfaces and traits, to define type, message and functionality.

### Stack frame
The Exception Factory uses <code>debug_backtrace()</code> to work out the namespace from which Glitch was called and uses this to decide which interfaces need to be generated and what needs to be rolled into the final Exception class.

It's aim is to have an interface named with each of the types defined in the original call to the Factory (eg <code>ERuntime</code>, <code>ENotFound</code>) defined _within the namespace of the originating call_ so that <code>catch</code> blocks can reference the type directly.

```php
namespace Any\Old\Namespace;

try {
    throw Glitch::ERuntime('message');
} catch(ERuntime $e) {
    // do something
}
```

Secondary to that, if the requested types are listed as primary exception types by the Factory then there will also be an interface to represent it in the root namespace (note the backslash in the catch block):

```php
namespace Any\Old\Namespace;

try {
    throw Glitch::ERuntime('message');
} catch(\ERuntime $e) {
    // do something
}
```

On top of that, the Factory will ensure there is an interface named <code>EGlitch</code> at _every_ namespace level up the tree to the target namespace so that developers can choose the granularity of catch blocks, ad hoc:

```php
namespace Any\Old\Namespace;

use MyLibrary\InnerNamespace\SomeClass;

$myLibrary = new SomeClass();

try {
    // This method will throw a Glitch
    $myLibrary->doAThing();
} catch(MyLibrary\InnerNamespace\EGlitch $e | MyLibrary\EGlitch $e | \EGlitch $e) {
    // All of the above tests will match
}
```

To increase compatibility with SPL exceptions, any E* types that have a corresponding SPL Exception class type will extend from _that_ type, rather than the root Exception class:

```php
namespace Any\Old\Namespace;

try {
    throw Glitch::ERuntime('message');
} catch(\RuntimeException $e) {
    // do something
}
```


And then for _any_ interface that is added to the final type definition, the equivalent <code>\<InterfaceName>Trait</code> trait will be added too, if it exists. This allows the inclusion of context specific functionality within a specific category of Exceptions without having to tie the functionality to a particular meaning.


As an example, given the fallowing Glitch call:

```php
namespace MyVendor\MyLibrary\SubFunctions;

trait ERuntimeTrait {
    public function extraFunction() {
        echo 'hello world';
    }
}

throw Glitch::ERuntime('message');
```

The resulting anonymous class will include:

- <code>MyVendor\MyLibrary\SubFunctions\ERuntime</code> interface
- <code>MyVendor\MyLibrary\SubFunctions\ERuntimeTrait</code> trait, with <code>extraFunction()</code>
- <code>\ERuntime</code> interface
- <code>MyVendor\MyLibrary\SubFunctions\EGlitch</code> interface
- <code>MyVendor\MyLibrary\EGlitch</code> interface
- <code>MyVendor\EGlitch</code> interface
- <code>\EGlitch</code> interface
- <code>\EGlitchTrait</code> trait (which is predefined)
- <code>\RuntimeException</code> base class


#### Repeated execution

Once the Factory has generated an Exception for a particular subgroup of requested types within a specific namespace, it is hashed and cached so that repeated calls to the Factory within the same context can just return a new instance of the anonymous class. The resulting performance overhead of general usage of Glitch Exceptions then tends to be trivial, while the _development_ overhead is **massively** reduced as there is no need to define individual Exception classes for every type of error in all of your libraries.
