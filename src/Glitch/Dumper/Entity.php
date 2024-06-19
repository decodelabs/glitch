<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Stack\Trace;

use ReflectionClass;

class Entity
{
    protected string $type;
    protected ?string $name = null;
    protected ?string $id = null;
    protected bool $open = true;

    protected ?int $objectId = null;
    protected ?string $hash = null;
    protected ?string $class = null;
    protected ?string $className = null;
    protected bool $sensitive = false;

    /**
     * @var array<string>|null
     */
    protected ?array $parents = null;

    /**
     * @var array<string>|null
     */
    protected ?array $interfaces = null;

    /**
     * @var array<string>|null
     */
    protected ?array $traits = null;

    protected ?string $file = null;
    protected ?int $startLine = null;
    protected ?int $endLine = null;

    protected ?string $text = null;
    protected ?string $definition = null;

    protected ?int $length = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $meta = null;

    /**
     * @var array<int|string, mixed>|null
     */
    protected ?array $values = null;

    protected bool $showValueKeys = true;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $properties = null;

    protected ?Trace $stackTrace = null;


    /**
     * @var array<string, bool>
     */
    protected array $sections = [
        'info' => false,
        'meta' => false,
        'text' => true,
        'definition' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    /**
     * Construct with required info
     */
    public function __construct(
        string $type
    ) {
        $this->setType($type);
    }


    /**
     * Import from dump yield
     */
    public function importDumpValue(
        object $object,
        string $target,
        mixed $value,
        Inspector $inspector
    ): void {
        $parts = explode(':', $target, 2);
        $target = (string)array_shift($parts);
        $key = array_pop($parts);

        $type = null;

        if (substr($target, 0, 1) === '^') {
            $target = substr($target, 1);

            $closer = function ($entity) {
                $entity->setOpen(false);
            };
        } else {
            $closer = null;
        }

        $method = 'set' . ucfirst($target);


        switch ($target) {
            case 'open':
            case 'showKeys':
                $type = 'bool';
                break;

            case 'startLine':
            case 'endLine':
            case 'length':
                $type = '?int';
                break;

            case 'type':
                $type = 'string';
                break;

            case 'name':
            case 'id':
            case 'objectId':
            case 'hash':
            case 'class':
            case 'className':
            case 'file':
            case 'text':
            case 'definition':
                $type = '?string';
                break;

            case 'parentClasses':
            case 'interfaces':
            case 'traits':
                $type = 'string[]';
                break;

            case 'value':
                if ($key === null) {
                    $method = 'setSingleValue';
                }

                $value = $inspector($value, $closer);
                break;

            case 'property':
                $value = $inspector($value, $closer);
                break;

            case 'meta':
                if ($key !== null) {
                    $value = $inspector($value, $closer);
                    break;
                }

                $method = 'setMetaList';

                // no break
            case 'values':
            case 'properties':
            case 'metaList':
                if ($value === null) {
                    return;
                }

                $value = $inspector->inspectList(
                    Coercion::toArray($value),
                    $closer
                );

                $type = 'array';
                break;

            case 'section':
                $method = 'setSectionVisible';
                $type = 'bool';
                break;

            case 'sections':
                $method = 'setSectionsVisible';
                $type = 'bool[]';
                break;

            case 'stackTrace':
                $type = Trace::class;
                break;

            case 'classMembers':
                /** @var array<string> $value */
                $this->checkValidity($value, 'string[]');

                $inspector->inspectClassMembers(
                    $object,
                    new ReflectionClass($object),
                    $this,
                    $value
                );
                return;

            default:
                throw Exceptional::UnexpectedValue(
                    'Invalid dump yield key : ' . $target
                );
        }

        $this->checkValidity($value, $type);

        if ($key !== null) {
            $this->{$method}($key, $value);
        } else {
            $this->{$method}($value);
        }
    }



    /**
     * Override type
     *
     * @return $this
     */
    public function setType(
        string $type
    ): static {
        $this->type = $type;
        $this->id = str_replace('.', '-', uniqid($type . '-', true));
        return $this;
    }

    /**
     * Static entity type name
     */
    public function getType(): string
    {
        return $this->type;
    }



    /**
     * Set entity instance name
     *
     * @return $this
     */
    public function setName(
        ?string $name
    ): static {
        $this->name = $name;
        return $this;
    }

    /**
     * Get entity instance name
     */
    public function getName(): ?string
    {
        return $this->name;
    }


    /**
     * Set default open state
     *
     * @return $this
     */
    public function setOpen(
        bool $open
    ): static {
        $this->open = $open;
        return $this;
    }

    /**
     * Get default open state
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * Set sensitive
     */
    public function setSensitive(
        bool $sensitive
    ): static {
        $this->sensitive = $sensitive;
        return $this;
    }

    /**
     * Is sensitive
     */
    public function isSensitive(): bool
    {
        return $this->sensitive;
    }


    /**
     * Set object id
     *
     * @return $this
     */
    public function setId(
        ?string $id
    ): static {
        $this->id = $id;
        return $this;
    }


    /**
     * Get object id
     */
    public function getId(): ?string
    {
        return $this->id;
    }


    /**
     * Set object id
     *
     * @return $this
     */
    public function setObjectId(
        ?int $id
    ): static {
        $this->objectId = $id;
        return $this;
    }

    /**
     * Get object id
     */
    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    /**
     * Set object / array hash
     *
     * @return $this
     */
    public function setHash(
        ?string $hash
    ): static {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Get object / array hash
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }


    /**
     * Set object class
     *
     * @return $this
     */
    public function setClass(
        ?string $class
    ): static {
        $this->class = $class;
        return $this;
    }

    /**
     * Get object class
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Set object class name
     *
     * @return $this
     */
    public function setClassName(
        ?string $className
    ): static {
        $this->className = $className;
        return $this;
    }

    /**
     * Get object class
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }


    /**
     * Set parent classes
     *
     * @return $this
     */
    public function setParentClasses(
        string ...$parents
    ): static {
        if (empty($parents)) {
            $parents = null;
        }

        $this->parents = $parents;
        return $this;
    }

    /**
     * Get parent classes
     *
     * @return array<string>|null
     */
    public function getParentClasses(): ?array
    {
        return $this->parents;
    }


    /**
     * Set interfaces
     *
     * @return $this
     */
    public function setInterfaces(
        string ...$interfaces
    ): static {
        if (empty($interfaces)) {
            $interfaces = null;
        }

        $this->interfaces = $interfaces;
        return $this;
    }

    /**
     * Get interfaces
     *
     * @return array<string>|null
     */
    public function getInterfaces(): ?array
    {
        return $this->interfaces;
    }


    /**
     * Set traits
     *
     * @return $this
     */
    public function setTraits(
        string ...$traits
    ): static {
        if (empty($traits)) {
            $traits = null;
        }

        $this->traits = $traits;
        return $this;
    }

    /**
     * Get traits
     *
     * @return array<string>|null
     */
    public function getTraits(): ?array
    {
        return $this->traits;
    }



    /**
     * Set source file
     *
     * @return $this
     */
    public function setFile(
        ?string $file
    ): static {
        $this->file = $file;
        return $this;
    }

    /**
     * Get source file
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Set source line
     *
     * @return $this
     */
    public function setStartLine(
        ?int $line
    ): static {
        $this->startLine = $line;
        return $this;
    }

    /**
     * Get source line
     */
    public function getStartLine(): ?int
    {
        return $this->startLine;
    }

    /**
     * Set source end line
     *
     * @return $this
     */
    public function setEndLine(
        ?int $line
    ): static {
        $this->endLine = $line;
        return $this;
    }

    /**
     * Get source end line
     */
    public function getEndLine(): ?int
    {
        return $this->endLine;
    }




    /**
     * Set object text
     *
     * @return $this
     */
    public function setText(
        ?string $text
    ): static {
        $this->text = $text;
        return $this;
    }

    /**
     * Get object text
     */
    public function getText(): ?string
    {
        return $this->text;
    }



    /**
     * Set definition code
     *
     * @return $this
     */
    public function setDefinition(
        ?string $definition
    ): static {
        $this->definition = $definition;
        return $this;
    }

    /**
     * Get definition code
     */
    public function getDefinition(): ?string
    {
        return $this->definition;
    }



    /**
     * Set item length
     *
     * @return $this
     */
    public function setLength(
        ?int $length
    ): static {
        $this->length = $length;
        return $this;
    }

    /**
     * Get item length
     */
    public function getLength(): ?int
    {
        return $this->length;
    }




    /**
     * Set meta value
     *
     * @return $this
     */
    public function setMeta(
        string $key,
        mixed $value
    ): static {
        $this->checkValidity($value);

        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * Get meta value
     */
    public function getMeta(
        string $key
    ): mixed {
        return $this->meta[$key] ?? null;
    }

    /**
     * Set meta list
     *
     * @param array<int|string, mixed> $meta
     * @return $this
     */
    public function setMetaList(
        array $meta
    ): static {
        foreach ($meta as $key => $value) {
            $this->setMeta((string)$key, $value);
        }

        return $this;
    }

    /**
     * Get all meta data
     *
     * @return array<string, mixed>
     */
    public function getMetaList(): ?array
    {
        return $this->meta;
    }

    /**
     * Has meta value
     */
    public function hasMeta(
        string $key
    ): bool {
        if ($this->meta === null) {
            return false;
        }

        return array_key_exists($key, $this->meta);
    }

    /**
     * Remove meta value
     *
     * @return $this
     */
    public function removeMeta(
        string $key
    ): static {
        unset($this->meta[$key]);
        return $this;
    }

    /**
     * Clear meta list
     *
     * @return $this
     */
    public function clearMeta(): static
    {
        $this->meta = [];
        return $this;
    }



    /**
     * Set value by key
     *
     * @return $this
     */
    public function setValue(
        int|string $key,
        mixed $value
    ): static {
        $this->checkValidity($value);

        $this->values[$key] = $value;
        return $this;
    }

    /**
     * Get value by key
     */
    public function getValue(
        int|string $key
    ): mixed {
        return $this->values[$key] ?? null;
    }

    /**
     * Set single value
     *
     * @return $this
     */
    public function setSingleValue(
        mixed $value
    ): static {
        $this->setValues([$value]);
        $this->setShowKeys(false);
        return $this;
    }

    /**
     * Get single value
     */
    public function getSingleValue(): mixed
    {
        return $this->values[0] ?? null;
    }

    /**
     * Set values list
     *
     * @param array<int|string, mixed>|null $values
     * @return $this
     */
    public function setValues(
        ?array $values
    ): static {
        if ($values !== null) {
            foreach ($values as $value) {
                $this->checkValidity($value);
            }
        }

        $this->values = $values;
        return $this;
    }

    /**
     * Get values list
     *
     * @return array<int|string, mixed>|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * Has value
     */
    public function hasValue(
        int|string $key
    ): bool {
        return isset($this->values[$key]);
    }

    /**
     * Remove value
     *
     * @return $this
     */
    public function removeValue(
        int|string $key
    ): static {
        unset($this->values[$key]);
        return $this;
    }

    /**
     * Clear values
     *
     * @return $this
     */
    public function clearValues(): static
    {
        $this->values = [];
        return $this;
    }


    /**
     * Set show keys
     *
     * @return $this
     */
    public function setShowKeys(
        bool $show
    ): static {
        $this->showValueKeys = $show;
        return $this;
    }

    /**
     * Should show values keys?
     */
    public function shouldShowKeys(): bool
    {
        return $this->showValueKeys;
    }



    /**
     * Set properties
     *
     * @param array<int|string, mixed> $properties
     * @return $this
     */
    public function setProperties(
        array $properties
    ): static {
        foreach ($properties as $key => $value) {
            $this->setProperty((string)$key, $value);
        }

        return $this;
    }

    /**
     * Get properties
     *
     * @return array<string, mixed>|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }


    /**
     * Set property
     *
     * @return $this
     */
    public function setProperty(
        string $key,
        mixed $value
    ): static {
        $this->checkValidity($value);
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Get property
     */
    public function getProperty(
        string $key
    ): mixed {
        return $this->properties[$key] ?? null;
    }

    /**
     * Has property
     */
    public function hasProperty(
        string $key
    ): bool {
        if (empty($this->properties)) {
            return false;
        }

        return array_key_exists($key, $this->properties);
    }

    /**
     * Remove property
     *
     * @return $this
     */
    public function removeProperty(
        string $key
    ): static {
        unset($this->properties[$key]);
        return $this;
    }

    /**
     * Remove all properties
     *
     * @return $this
     */
    public function clearProperties(): static
    {
        $this->properties = [];
        return $this;
    }



    /**
     * Set stack trace
     *
     * @return $this
     */
    public function setStackTrace(
        Trace $trace
    ): static {
        $this->stackTrace = $trace;
        return $this;
    }

    /**
     * Get stack trace
     */
    public function getStackTrace(): ?Trace
    {
        return $this->stackTrace;
    }



    /**
     * Check value for Entity validity
     */
    protected function checkValidity(
        mixed $value,
        string $type = null
    ): void {
        if ($type !== null) {
            if (!$this->checkTypeValidity($value, $type)) {
                throw Exceptional::UnexpectedValue(
                    'Invalid dump yield value type (' . $type . ')'
                );
            }
        }

        switch (true) {
            case $value === null:
            case is_bool($value):
            case is_int($value):
            case is_float($value):
            case is_string($value):
            case $type !== null && (
                is_array($value) ||
                $value instanceof $type
            ):
            case $value instanceof Entity:
                return;

            default:
                throw Exceptional::UnexpectedValue(
                    'Invalid sub-entity type - must be scalar or Entity',
                    null,
                    $value
                );
        }
    }

    /**
     * Check value type
     */
    protected function checkTypeValidity(
        mixed $value,
        string $type
    ): bool {
        // Nullable
        if (substr($type, 0, 1) === '?') {
            if ($value === null) {
                return true;
            }

            $type = substr($type, 1);
        }

        // List
        if (substr($type, -2) == '[]') {
            if (!is_array($value)) {
                return false;
            }

            $type = substr($type, 0, -2);

            foreach ($value as $innerVal) {
                if (!$this->checkTypeValidity($innerVal, $type)) {
                    return false;
                }
            }

            return true;
        }

        switch ($type) {
            case 'bool': return is_bool($value);
            case 'int': return is_int($value);
            case 'float': return is_float($value);
            case 'string': return is_string($value);
            case 'array': return is_array($value);
            default: return $value instanceof $type;
        }
    }



    /**
     * Hide entity section
     *
     * @return $this
     */
    public function hideSection(
        string $name
    ): static {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = false;
        }

        return $this;
    }

    /**
     * Show entity section
     *
     * @return $this
     */
    public function showSection(
        string $name
    ): static {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = true;
        }

        return $this;
    }

    /**
     * Set entity section visible
     *
     * @return $this
     */
    public function setSectionVisible(
        string $name,
        bool $visible
    ): static {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = $visible;
        }

        return $this;
    }

    /**
     * Is section visible
     */
    public function isSectionVisible(
        string $name
    ): bool {
        return $this->sections[$name] ?? false;
    }

    /**
     * Set section visibility map
     *
     * @param array<string, bool> $sections
     * @return $this
     */
    public function setSectionsVisible(
        array $sections
    ): static {
        foreach ($sections as $section => $visible) {
            $this->setSectionVisible($section, (bool)$visible);
        }

        return $this;
    }

    /**
     * Get section visibility
     *
     * @return array<string, bool>
     */
    public function getSectionVisibility(): array
    {
        return $this->sections;
    }
}
