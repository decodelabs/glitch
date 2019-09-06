# Rationale for Glitch Exceptions
PHP (and, as it happens, most modern languages) rely on a fairly rudimentary concept of Exceptions to handle errors at runtime. The principle is generally sound, however the implementation suffers from a handful of key flaws.

Primarily, _meaning_ is inferred by the class name of the Exception being thrown.

```php
throw new OutOfBoundsException('Index is not in range');
```

While this _works_, it is fundamentally limiting; PHP does not have multiple-inheritance and so can only convey one _meaning_ directly via the class name, does not imply any context of scope (ie, _where_ the error occurred), and requires writing unnecessary boilerplate code to represent every form of _meaning_ being relayed.

```php
namespace MyLibrary {
    class TooMuchTypingException extends \RuntimeException {}
}

namespace MyOtherLibrary {
    class TooMuchTypingException extends \RuntimeException {}
}
```

Having libraries that need to convey the same _meaning_ but from different contexts compound this problem by either having to redefine the same class in their own namespace, or rely on traits to share functionality.

The structure of the class that makes an Exception should be dedicated to providing the functionality required to convey the **state** of the application in the context it is used.


#### Multiple meanings
While classes cannot convey multiple messages, interfaces _can_.

```php
namespace MyLibrary;

interface ENotFound {}
interface EFailedService {}

class MethodNotFoundException extends \RuntimeException implements NotFoundError, FailedServiceError {}

try {
    throw new MethodNotFoundException('Test');
} catch(ENotFound $e) {}
```

However interfaces alone cannot immediately infer where the problem originated as you still require a class to be defined for each context from which the Exception may be thrown.

Also, this requires writing and loading **lots** of boilerplate code to represent what are ultimately simple, static messages.


### Solution
Instead of defining a class for every Exception that may be thrown, interfaces can be generated at runtime to represent a specific meaning of an error, and assigned to anonymous classes as and when they are needed.

The generated interfaces can be placed throughout the namespace tree so that try / catch blocks can check for the same message at any level of namespace depth, and the resulting anonymous class can automatically extend from PHP's built in set of named Exceptions.

Glitch attempts to do all of this automatically from the minimum amount of input.

```php
namespace MyLibrary\AThingThatDoesStuff;

class Amazeballs {

    public function doStuff() {
        throw \Glitch::{'ENotFound,EFailedService'}(
            'Service "doStuff" cannot be found'
        );
    }
}
```

The resulting object would look something like this:

```php
namespace {
    interface EGlitch {}
    interface ENotFound {
        const EXTEND = 'RuntimeException';
    }
}

namespace MyLibrary {
    interface EGlitch extends \EGlitch {}
}

namespace MyLibrary\AThingThatDoesStuff {

    interface EGlitch extends MyLibrary\EGlitch {}
    interface ENotFound extends EGlitch, \ENotFound {}
    interface EFailedService extends EGlitch {}

    $e = new class($message) extends \RuntimeException implements ENotFound,EFailedService {}
}
```

The generated Exception can be checked for in a try / catch block with _any_ of those scoped interfaces, root interfaces or PHP's RuntimeException.

Any functionality that the Exception then needs to convey the **state** of the error can then either be mixed in via traits, or by extending from an intermediate class that defines the necessary methods.
