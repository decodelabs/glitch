# Glitch — Package Specification

> **Cluster:** `observability`
> **Language:** `php`
> **Milestone:** `m2`
> **Repo:** `https://github.com/decodelabs/glitch`
> **Role:** Error handling

This document describes the purpose, contracts, and design of **Glitch** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Glitch for debugging and error handling.
- Contributors **maintaining or extending** Glitch.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Glitch provides better tools for when things go wrong in PHP applications. It dramatically improves error handling and inspection during development by providing deep data inspection tools and an exception handling interface. Glitch offers a comprehensive debugging experience with HTML and CLI renderers, customizable styling, and integration with Symfony VarDumper for compatibility. The package provides global `dump()` and `dd()` functions that mirror Symfony's VarDumper interface, maintaining compatibility while using Glitch's own rendering system when Symfony is not available. Glitch can be registered as the default error handler, exception handler, and shutdown handler, providing a unified error reporting experience.

### 1.2 Non-Goals

Glitch does **not**:

- Provide application-specific business logic or domain models
- Implement routing, middleware, or request handling
- Provide database abstraction or ORM functionality
- Implement user authentication or authorization
- Provide templating or view rendering (beyond error pages)
- Implement session management or caching
- Provide asset management or bundling
- Implement form handling or validation
- Provide application scaffolding or code generation
- Implement deployment or DevOps tooling
- Provide testing frameworks or test runners
- Implement logging backends (uses PSR-3 logger interface)
- Provide performance profiling or monitoring
- Implement APM or distributed tracing

Glitch focuses on providing debugging and error handling tools, not on implementing application features or infrastructure.

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `observability` (see Chorus taxonomy)
- Glitch is positioned as an observability tool that provides debugging and error handling capabilities for PHP applications. It sits at a high level in the dependency graph, depending on several core packages (Coercion, Enlighten, Exceptional, Kingdom, Monarch, Nuance, Remnant) and integrating with PSR-3 logging. Glitch is used by frameworks like Fabric and applications built on the Decode Labs ecosystem to provide a unified error reporting and debugging experience. It integrates with Nuance for data inspection and rendering, Remnant for stack trace handling, and Exceptional for exception management.

### 2.2 Typical Usage Contexts

Typical places Glitch appears:

- Application error handlers (registered as default error/exception/shutdown handlers)
- Development debugging (via `dump()` and `dd()` functions)
- Exception rendering and display
- Stack trace visualization
- Data inspection and dumping
- Error page rendering (production mode)
- CLI error output
- HTTP error responses
- Framework error handling (e.g., Fabric)
- Development tooling integration

Glitch is intended to be used whenever an application needs to:
- Display debug information during development
- Handle and display exceptions
- Inspect data structures
- Visualize stack traces
- Provide error pages in production
- Output debugging information in CLI contexts

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Glitch`
  Main service that orchestrates dumping, exception handling, and error reporting. Implements `LoggerAwareInterface`, `ExceptionLogger`, and `PureService`. Provides methods for dumping values, handling exceptions, registering as error handlers, managing renderers and transports, and gathering statistics.

- `DecodeLabs\Glitch\Renderer`
  Interface for rendering dumps and exceptions. Extends Nuance's `Renderer` interface. Defines methods for rendering dump views and exception views, with production override support.

- `DecodeLabs\Glitch\Renderer\Html`
  HTML renderer for dumps and exceptions. Extends Nuance's `HtmlRenderer`. Provides rich HTML output with syntax highlighting, stack trace visualization, and environment information. Supports custom CSS files for styling. Renders output in iframes to avoid breaking page layout.

- `DecodeLabs\Glitch\Renderer\Cli`
  CLI renderer for dumps and exceptions. Extends Nuance's `CliRenderer`. Provides colored terminal output with formatted statistics and exception information.

- `DecodeLabs\Glitch\Renderer\Text`
  Plain text renderer for dumps and exceptions. Extends Nuance's `TextRenderer`. Provides simple text output.

- `DecodeLabs\Glitch\RendererTrait`
  Trait providing common renderer functionality. Handles production mode detection, rendering logic, and buffer export.

- `DecodeLabs\Glitch\Transport`
  Interface for delivering rendered dumps and exceptions. Defines methods for sending dumps and exceptions.

- `DecodeLabs\Glitch\Transport\Http`
  HTTP transport that sends rendered output via HTTP headers and echo. Handles content type, cache control, and CORS headers.

- `DecodeLabs\Glitch\Transport\Stdout`
  Standard output transport that sends rendered output to STDOUT.

- `DecodeLabs\Glitch\Dump`
  Container for dump information including entities, statistics, and stack trace. Implements `IteratorAggregate` and `Countable`.

- `DecodeLabs\Glitch\Packet`
  Container for rendered output with body and content type.

- `DecodeLabs\Glitch\Stat`
  Represents a statistic with key, name, value, and optional renderer callback.

- `DecodeLabs\Glitch\Renderer\Html\ZestManifest`
  Helper for loading Zest asset manifests for HTML renderer styling and scripts.

### 3.2 Main Entry Points

The main usage pattern is through global functions:

```php
// Dump and continue
dump($var1, $var2, ...);

// Dump and die
dd($var1, $var2, ...);
```

Or via the service:

```php
use DecodeLabs\Glitch;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);
$glitch->dump('hello');
$glitch->dumpDie('goodbye');
$glitch->dumpException($exception);
```

Register as error handler:

```php
$glitch->registerAsErrorHandler();
```

Customize renderer:

```php
$glitch->setRenderer($customRenderer);
$glitch->getRenderer()->setCustomCssFile('path/to/custom.css');
```

Customize transport:

```php
$glitch->setTransport($customTransport);
```

---

## 4. Dependencies

### 4.1 Decode Labs

- `decodelabs/coercion` (required)
  Used for type coercion in stat rendering and value conversion.

- `decodelabs/enlighten` (required)
  Used for syntax highlighting in HTML renderer stack trace display.

- `decodelabs/exceptional` (required)
  Used for exception handling and creating error exceptions from PHP errors.

- `decodelabs/kingdom` (required)
  Used for service container integration (Glitch implements `PureService`).

- `decodelabs/monarch` (required)
  Used for global service location, environment detection (production mode), and exception logger registration.

- `decodelabs/nuance` (required)
  Used for data inspection and entity rendering. Glitch renderers extend Nuance renderers.

- `decodelabs/remnant` (required)
  Used for stack trace creation and frame management. Glitch uses Remnant's `Trace` and `Frame` classes.

### 4.2 External

- `symfony/polyfill-mbstring` (required, ^1.33)
  Used for multibyte string operations.

- `psr/log` (required, ^3.0.2)
  Used for PSR-3 logging interface (`LoggerAwareInterface`).

### 4.3 Optional Integrations

- `symfony/var-dumper` (dev dependency, ^5|^6|^7)
  Detected at runtime if installed, used for compatibility with Symfony's VarDumper interface. If Symfony VarDumper is loaded, Glitch's global functions delegate to it.

- `decodelabs/zest` (optional)
  Detected at runtime if installed, used for asset manifest loading in HTML renderer via `ZestManifest`.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- Glitch service is registered with Monarch as exception logger on construction
- Start time is set to current microtime on construction
- Default stat gatherer is registered on construction
- Renderer is lazy-loaded based on SAPI (HTML for HTTP, CLI for CLI)
- Transport is lazy-loaded based on SAPI (Http for HTTP, Stdout for CLI)
- Dumps create stack traces using Remnant
- Dumps gather statistics from registered gatherers
- Dumps inspect entities using Nuance
- Dumps render using active renderer
- Dumps are sent via active transport
- `dump()` does not exit, `dd()` exits
- `dumpDie()` exits after dumping
- `dumpException()` can exit or continue
- HTML renderer wraps output in iframe when not final or when dumped in buffer
- HTML renderer checks for production mode before rendering
- CLI renderer always renders (RenderInProduction = true)
- Text renderer always renders (RenderInProduction = true)
- Production mode detection uses Monarch
- Error handler converts PHP errors to Exceptional exceptions
- Error handler respects error_reporting() settings
- Error handler logs notices/deprecations in production instead of throwing
- Exception handler logs exceptions and renders them
- Exception handler uses error page renderer in production if set
- Shutdown handler catches fatal errors
- Stat gatherers are called for each dump
- Stats can have custom renderers
- Renderer production override can force rendering in production
- Transport sends packets with appropriate headers
- HTTP transport sets headers only if not already sent
- HTTP transport sets CORS headers
- HTTP transport calls header buffer sender if set
- Global functions check for Symfony VarDumper compatibility
- Global functions use Monarch to get Glitch service
- Global functions create appropriate anchors for stack traces

### 5.2 Input & Output Contracts

**Glitch Methods:**
- `dump(mixed $var, mixed ...$vars): void` — Dumps values and continues execution. No return value.
- `dumpDie(mixed $var, mixed ...$vars): void` — Dumps values and exits. No return value.
- `dumpValues(array $values, ?Anchor $anchor = null, bool $exit = true): void` — Dumps array of values with optional anchor and exit flag. No return value.
- `dumpException(Throwable $exception, bool $exit = true): void` — Dumps exception with optional exit flag. No return value.
- `stackTrace(?Anchor $anchor = null): Trace` — Creates stack trace with optional anchor. Returns Remnant Trace instance.
- `registerAsErrorHandler(): static` — Registers Glitch as error, exception, and shutdown handlers. Returns self for chaining.
- `handleError(int $level, string $message, string $file, int $line): bool` — Handles PHP error. Returns true if handled, false otherwise.
- `handleException(Throwable $exception): void` — Handles uncaught exception. No return value.
- `handleShutdown(): void` — Handles fatal errors on shutdown. No return value.
- `logException(Throwable $exception): void` — Logs exception via PSR-3 logger and/or log listener. No return value.
- `setStartTime(float $time): static` — Sets application start time. Returns self for chaining.
- `getStartTime(): float` — Gets application start time. Returns float.
- `setRenderer(Renderer $renderer): static` — Sets custom renderer. Returns self for chaining.
- `getRenderer(): Renderer` — Gets active renderer (lazy-loaded). Returns Renderer instance.
- `getActiveRenderer(): Renderer` — Gets active renderer with fallback to Text if headers sent. Returns Renderer instance.
- `useTextRenderer(): static` — Sets text renderer. Returns self for chaining.
- `setTransport(Transport $transport): static` — Sets custom transport. Returns self for chaining.
- `getTransport(): Transport` — Gets active transport (lazy-loaded). Returns Transport instance.
- `setLogger(LoggerInterface $logger): void` — Sets PSR-3 logger. No return value.
- `getLogger(): ?LoggerInterface` — Gets PSR-3 logger. Returns LoggerInterface or null.
- `setLogListener(?callable $listener): static` — Sets log listener callback. Returns self for chaining.
- `getLogListener(): ?Closure` — Gets log listener callback. Returns Closure or null.
- `setHeaderBufferSender(?callable $sender): static` — Sets header buffer sender callback. Returns self for chaining.
- `getHeaderBufferSender(): ?Closure` — Gets header buffer sender callback. Returns Closure or null.
- `setErrorPageRenderer(?callable $renderer): static` — Sets error page renderer callback. Returns self for chaining.
- `getErrorPageRenderer(): ?Closure` — Gets error page renderer callback. Returns Closure or null.
- `registerStatGatherer(string $name, callable $gatherer): static` — Registers stat gatherer. Returns self for chaining.
- `getStatGatherers(): array<string,Closure>` — Gets all stat gatherers. Returns array of closures.
- `gatherDefaultStats(Dump $dump): void` — Default stat gatherer. No return value.
- `hasDumpedInBuffer(): bool` — Checks if dump was made in output buffer. Returns bool.
- `getVendorPath(): string` — Gets vendor directory path. Returns string.

**Renderer Interface:**
- `renderDumpView(Dump $dump, bool $final): Packet` — Renders dump view. Returns Packet instance.
- `renderExceptionView(Throwable $exception, Dump $dataDump): Packet` — Renders exception view. Returns Packet instance.
- `setProductionOverride(bool $flag): static` — Sets production override flag. Returns self for chaining.
- `getProductionOverride(): bool` — Gets production override flag. Returns bool.

**Transport Interface:**
- `__construct(Glitch $service): void` — Constructs transport with Glitch service. No return value.
- `sendDump(Packet $packet, bool $final): void` — Sends dump packet. No return value.
- `sendException(Packet $packet, bool $final): void` — Sends exception packet. No return value.

**Dump Methods:**
- `__construct(Trace $trace): void` — Constructs dump with stack trace. No return value.
- `addStats(Stat ...$stats): static` — Adds statistics. Returns self for chaining.
- `getStat(string $key): ?Stat` — Gets statistic by key. Returns Stat or null.
- `removeStat(string $key): static` — Removes statistic by key. Returns self for chaining.
- `getStats(): array<string,Stat>` — Gets all statistics. Returns array of Stat instances.
- `clearStats(): static` — Clears all statistics. Returns self for chaining.
- `getTrace(): Trace` — Gets stack trace. Returns Remnant Trace instance.
- `addEntity(mixed $entity): static` — Adds entity to dump. Returns self for chaining.
- `getEntities(): array<mixed>` — Gets all entities. Returns array of values.
- `count(): int` — Gets entity count. Returns int.

**Stat Methods:**
- `__construct(string $key, string $name, mixed $value): void` — Constructs stat with key, name, and value. No return value.
- `getKey(): string` — Gets stat key. Returns string.
- `getName(): string` — Gets stat name. Returns string.
- `setRenderer(?callable $renderer): static` — Sets renderer callback. Returns self for chaining.
- `render(): ?string` — Renders stat value. Returns string or null.

**Packet Methods:**
- `__construct(string $body, string $contentType): void` — Constructs packet with body and content type. No return value.
- `getBody(): string` — Gets packet body. Returns string.
- `getContentType(): string` — Gets content type. Returns string.

**Global Functions:**
- `dump(mixed $var, mixed ...$vars): void` — Dumps values and continues. No return value.
- `dd(mixed $var, mixed ...$vars): void` — Dumps values and exits. No return value (never returns).
- `dd2(mixed $var, mixed ...$vars): never` — Simple dump and die using print_r. Never returns.

---

## 6. Error Handling

- Renderer failures may cause dumps to fail silently or fall back to text output
- Transport failures may cause output to be lost
- Stat gatherer failures are caught and logged, but don't stop dump process
- Exception handler failures may cause recursive exception handling
- Error handler failures may cause PHP to use default error handling
- Shutdown handler failures may cause fatal errors to be unhandled
- Logger failures are caught and ignored
- Log listener failures are caught and ignored
- Header buffer sender failures are caught and ignored
- Error page renderer failures fall back to default exception rendering
- Nuance inspection failures may cause entities to be rendered as strings
- Remnant trace creation failures may cause dumps to fail
- Enlighten highlighting failures may cause source code to be displayed without highlighting
- Vendor path resolution failures throw exceptions
- Invalid renderer implementations may cause rendering to fail
- Invalid transport implementations may cause output to be lost
- Invalid stat gatherers may cause statistics to be missing
- Production mode detection failures default to non-production
- Output buffer handling failures may cause output to be corrupted

---

## 7. Configuration & Extensibility

- Renderer can be customized by implementing `Renderer` interface
- Transport can be customized by implementing `Transport` interface
- Stat gatherers can be registered to add custom statistics
- HTML renderer supports custom CSS files for styling
- Production override can force rendering in production mode
- Error page renderer can be customized for production error pages
- Header buffer sender can be customized for header management
- Log listener can be customized for exception logging
- PSR-3 logger can be set for structured logging
- Start time can be set for timing calculations
- Global functions integrate with Symfony VarDumper if available
- HTML renderer uses Zest manifest if available for asset loading
- Renderer selection is automatic based on SAPI
- Transport selection is automatic based on SAPI
- HTML renderer falls back to Text renderer if headers already sent
- HTML renderer wraps output in iframe to avoid breaking page layout
- CLI renderer uses colored output via Terminus
- Text renderer provides plain text output
- Exception rendering can be customized per renderer
- Dump rendering can be customized per renderer
- Stack trace rendering can be customized per renderer
- Environment information rendering can be customized per renderer
- Stat rendering can be customized per stat

---

## 8. Interactions with Other Packages

### 8.1 Nuance

Glitch uses Nuance for:
- Data inspection and entity creation
- Entity rendering (HTML, CLI, Text formats)
- Custom dumpable interface support
- Value rendering and formatting

Glitch renderers extend Nuance renderers and use Nuance's inspector for data inspection.

### 8.2 Remnant

Glitch uses Remnant for:
- Stack trace creation and management
- Frame representation and formatting
- Anchor-based trace filtering
- Location prettification

Glitch creates Remnant traces for dumps and exceptions, and uses Remnant frames for stack trace display.

### 8.3 Exceptional

Glitch uses Exceptional for:
- Exception creation from PHP errors
- Exception parameter management
- HTTP status code handling
- Exception stack trace access

Glitch converts PHP errors to Exceptional exceptions and handles Exceptional exceptions specially (e.g., HTTP codes).

### 8.4 Monarch

Glitch uses Monarch for:
- Global service location
- Environment mode detection (production vs development)
- Exception logger registration
- Service container integration

Glitch registers itself with Monarch as an exception logger and uses Monarch to detect production mode.

### 8.5 Kingdom

Glitch uses Kingdom for:
- Service container integration (implements `PureService`)
- Service registration and resolution

### 8.6 Enlighten

Glitch uses Enlighten for:
- Syntax highlighting in HTML renderer stack trace display
- Source code extraction from files

### 8.7 Coercion

Glitch uses Coercion for:
- Type coercion in stat rendering
- Value conversion and stringification

### 8.8 PSR-3 Log

Glitch uses PSR-3 Log for:
- Structured exception logging
- Logger interface implementation

### 8.9 Symfony VarDumper

Glitch integrates with Symfony VarDumper for:
- Compatibility with existing Symfony-based code
- Delegation of dump/dd calls when VarDumper is loaded

If Symfony VarDumper is loaded, Glitch's global functions delegate to it while maintaining compatibility.

### 8.10 Zest

Glitch optionally uses Zest for:
- Asset manifest loading in HTML renderer
- CSS and JavaScript asset management

Detected at runtime if installed, used via `ZestManifest` for loading compiled assets.

### 8.11 Other Packages

Glitch may be used by:
- `decodelabs/fabric` — Framework error handling
- `decodelabs/clip` — CLI error handling
- Applications built on Decode Labs ecosystem

---

## 9. Usage Examples

### 9.1 Basic Dumping

```php
use DecodeLabs\Glitch;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);

// Dump and continue
$glitch->dump('hello', ['foo' => 'bar'], $object);

// Dump and die
$glitch->dumpDie('goodbye');
```

### 9.2 Global Functions

```php
// Dump and continue
dump($var1, $var2, $var3);

// Dump and die
dd($var1, $var2, $var3);
```

### 9.3 Exception Handling

```php
use DecodeLabs\Glitch;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);

// Register as error handler
$glitch->registerAsErrorHandler();

// Dump exception
try {
    // code that may throw
} catch (\Throwable $e) {
    $glitch->dumpException($e, exit: false);
}
```

### 9.4 Custom Renderer

```php
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Monarch;

class MyRenderer implements Renderer
{
    // Implement Renderer interface
}

$glitch = Monarch::getService(Glitch::class);
$glitch->setRenderer(new MyRenderer($glitch));
```

### 9.5 Custom Transport

```php
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Transport;
use DecodeLabs\Monarch;

class MyTransport implements Transport
{
    public function __construct(Glitch $service) {}
    
    public function sendDump(Packet $packet, bool $final): void
    {
        // Send to custom destination
    }
    
    public function sendException(Packet $packet, bool $final): void
    {
        // Send to custom destination
    }
}

$glitch = Monarch::getService(Glitch::class);
$glitch->setTransport(new MyTransport($glitch));
```

### 9.6 Custom Statistics

```php
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dump;
use DecodeLabs\Glitch\Stat;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);

$glitch->registerStatGatherer('custom', function (Dump $dump, Glitch $glitch) {
    $dump->addStats(
        new Stat('custom', 'Custom stat', $customValue)
            ->setRenderer(fn ($value) => (string)$value)
    );
});
```

### 9.7 Custom CSS Styling

```php
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Renderer\Html;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);
$renderer = $glitch->getRenderer();

if ($renderer instanceof Html) {
    $renderer->setCustomCssFile('path/to/custom.css');
}
```

### 9.8 Production Error Page

```php
use DecodeLabs\Glitch;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);

$glitch->setErrorPageRenderer(function (\Throwable $exception, Glitch $glitch) {
    // Render custom error page
    include 'error-page.php';
});
```

### 9.9 Logging Integration

```php
use DecodeLabs\Glitch;
use DecodeLabs\Monarch;
use Psr\Log\LoggerInterface;

$glitch = Monarch::getService(Glitch::class);
$glitch->setLogger($logger);

// Exceptions will be logged via PSR-3 logger
```

### 9.10 Log Listener

```php
use DecodeLabs\Glitch;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);

$glitch->setLogListener(function (\Throwable $exception) {
    // Custom exception logging logic
    error_log((string)$exception);
});
```

### 9.11 Start Time

```php
use DecodeLabs\Glitch;
use DecodeLabs\Monarch;

$glitch = Monarch::getService(Glitch::class);
$glitch->setStartTime(microtime(true));

// Statistics will show time since start
```

---

## 10. Implementation Notes (for Contributors)

### 10.1 Internal Architecture

At a high level, Glitch:
- Provides global functions for dumping (`dump()`, `dd()`)
- Integrates with Symfony VarDumper for compatibility
- Uses Nuance for data inspection and rendering
- Uses Remnant for stack trace management
- Provides HTML, CLI, and Text renderers
- Provides Http and Stdout transports
- Registers as error/exception/shutdown handlers
- Gathers statistics from registered gatherers
- Renders dumps and exceptions via renderers
- Sends output via transports
- Integrates with PSR-3 logging
- Detects production mode via Monarch
- Wraps HTML output in iframes to avoid breaking pages

### 10.2 Dump Flow

1. User calls `dump()` or `dd()` (global functions or service methods)
2. Glitch creates Remnant trace with appropriate anchor
3. Glitch creates Dump container with trace
4. Glitch calls registered stat gatherers
5. Glitch adds entities to dump
6. Glitch gets active renderer (HTML, CLI, or Text)
7. Renderer inspects entities using Nuance
8. Renderer renders dump view to Packet
9. Glitch gets active transport (Http or Stdout)
10. Transport sends packet
11. If `dd()` or `exit=true`, script exits

### 10.3 Exception Flow

1. Exception is thrown or caught
2. Glitch logs exception via PSR-3 logger and/or log listener
3. If production mode and error page renderer set, use it
4. Otherwise, create Remnant trace from exception
5. Create Dump container with trace
6. Call registered stat gatherers
7. Get active renderer
8. Renderer inspects exception using Nuance
9. Renderer renders exception view to Packet
10. Get active transport
11. Transport sends packet
12. If `exit=true`, script exits

### 10.4 Error Handler Flow

1. PHP error occurs
2. Glitch error handler is called
3. Check if error should be reported (error_reporting())
4. Convert error to Exceptional exception
5. If production mode and notice/deprecation, log and return true
6. Otherwise, throw exception (which triggers exception handler)

### 10.5 Renderer Selection

- HTML renderer is used for HTTP SAPI
- CLI renderer is used for CLI/phpdbg SAPI
- Text renderer is used as fallback if headers already sent
- Renderer can be manually set
- Production override can force rendering in production

### 10.6 Transport Selection

- Http transport is used for HTTP SAPI
- Stdout transport is used for CLI/phpdbg SAPI
- Transport can be manually set
- Http transport sets headers and CORS
- Stdout transport just echoes output

### 10.7 HTML Renderer Features

- Wraps output in iframe to avoid breaking page layout
- Uses Zest manifest for asset loading if available
- Supports custom CSS files
- Provides syntax highlighting via Enlighten
- Shows stack traces with source code
- Shows environment information (PHP version, superglobals, etc.)
- Shows statistics (time, memory, location)
- Provides interactive stack trace filtering

### 10.8 CLI Renderer Features

- Uses colored output via Terminus
- Shows statistics in formatted line
- Shows exception information
- Shows stack traces
- Always renders (even in production)

### 10.9 Text Renderer Features

- Plain text output
- Simple formatting
- Always renders (even in production)
- Used as fallback when headers already sent

### 10.10 Stat Gathering

- Default stat gatherer adds time, memory, peak memory, and location
- Custom stat gatherers can be registered
- Stats can have custom renderers
- Stats are rendered per renderer type

### 10.11 Production Mode

- Detected via Monarch
- HTML renderer does not render in production (unless override)
- CLI renderer always renders
- Text renderer always renders
- Error handler logs notices/deprecations in production
- Exception handler uses error page renderer in production if set

### 10.12 Symfony VarDumper Integration

- Global functions check if VarDumper is loaded
- If loaded, functions delegate to VarDumper
- VarDumper handler is set to use Glitch for actual rendering
- Maintains compatibility with Symfony-based code

### 10.13 Performance Considerations

- Renderer and transport are lazy-loaded
- Stat gatherers are called only when dumping
- Nuance inspection is done per entity
- HTML renderer caches asset paths
- Iframe wrapping avoids output buffering issues
- Production mode check is cached

### 10.14 Gotchas & Historical Decisions

- Global functions must check for Symfony VarDumper compatibility
- HTML renderer wraps output in iframe to avoid breaking pages
- HTML renderer falls back to Text if headers already sent
- Production mode detection uses Monarch (must be initialized)
- Error handler respects error_reporting() settings
- Exception handler may cause recursive exceptions if it fails
- Shutdown handler catches fatal errors
- Stat gatherers are called for each dump (may be expensive)
- Renderer production override can force rendering in production
- Transport selection is automatic based on SAPI
- HTTP transport sets headers only if not already sent
- HTTP transport sets CORS headers for cross-origin access
- Header buffer sender can be used to manage output buffering
- Error page renderer is only used in production mode
- Log listener is called in addition to PSR-3 logger
- Start time is set on construction (can be overridden)
- Vendor path resolution uses Composer ClassLoader reflection
- Zest manifest is optional (falls back to direct asset paths)
- Custom CSS files are loaded inline in HTML renderer
- Stack trace rendering uses Enlighten for syntax highlighting
- Environment information includes superglobals and filtered GLOBALS
- Exception entity rendering disables text and stack sections
- Dump entity rendering shows all entities in sequence
- Packet contains body and content type
- Stat rendering uses callable renderers if set
- Remnant trace creation uses anchors for filtering
- Global functions create appropriate anchors for stack traces
- `dd2()` function provides simple print_r-based dumping
- Output buffer handling clears buffers on exit
- Dumped in buffer flag tracks if dump was made in buffer

---

## 11. Testing & Quality

- **Code Quality Score:** 3.5/5
- **README Quality Score:** 3/5
- **Documentation Score:** 0/5 (this spec)
- **Test Coverage Score:** 0/5

See `composer.json` for supported PHP versions.

---

## 12. Roadmap & Future Ideas

- Enhanced documentation and examples
- Additional renderer types (JSON, XML)
- Additional transport types (file, network)
- Enhanced error page customization
- Performance profiling integration
- Enhanced stack trace visualization
- Better production error handling
- Enhanced statistics gathering
- Better integration with logging systems
- Enhanced CLI output formatting
- Better iframe handling
- Enhanced asset management
- Additional customization options
- Better error recovery
- Enhanced exception handling

---

## 13. References

- [Nuance Package](https://github.com/decodelabs/nuance) — Data inspection and rendering
- [Remnant Package](https://github.com/decodelabs/remnant) — Stack trace management
- [Exceptional Package](https://github.com/decodelabs/exceptional) — Exception handling
- [Monarch Package](https://github.com/decodelabs/monarch) — Global service location
- [Kingdom Package](https://github.com/decodelabs/kingdom) — Service container
- [Enlighten Package](https://github.com/decodelabs/enlighten) — Syntax highlighting
- [Coercion Package](https://github.com/decodelabs/coercion) — Type coercion
- [Fabric Package](https://github.com/decodelabs/fabric) — Framework using Glitch
- [Clip Package](https://github.com/decodelabs/clip) — CLI kernel using Glitch
- [Symfony VarDumper](https://symfony.com/doc/current/components/var_dumper.html) — VarDumper compatibility
- [PSR-3 Log](https://www.php-fig.org/psr/psr-3/) — Logging interface
- [Chorus Package Index](../../../chorus/config/packages.json) — Ecosystem metadata

