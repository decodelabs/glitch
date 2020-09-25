<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Dumper;

use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Dumper\Inspector;

class Entity
{
    protected $type;
    protected $name;
    protected $id;
    protected $open = true;

    protected $objectId;
    protected $hash;
    protected $class;
    protected $className;
    protected $parents;
    protected $interfaces;
    protected $traits;
    protected $file;
    protected $startLine;
    protected $endLine;

    protected $text;
    protected $definition;

    protected $length;
    protected $meta;

    protected $values;
    protected $showValueKeys = true;

    protected $properties;
    protected $stackTrace;

    protected $sections = [
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
    public function __construct(string $type)
    {
        $this->setType($type);
    }


    /**
     * Import from dump yield
     */
    public function importDumpValue(object $object, string $target, $value, Inspector $inspector): void
    {
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

        $method = 'set'.ucfirst($target);


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

            case 'meta':
            case 'property':
                $value = $inspector($value, $closer);
                break;

            case 'values':
            case 'properties':
            case 'metaList':
                if ($value === null) {
                    return;
                }

                $value = $inspector->inspectList($value, $closer);
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
                $this->checkValidity($value, 'string[]');

                $inspector->inspectClassMembers(
                    $object, new \ReflectionClass($object), $this, $value
                );
                return;

            default:
                throw \Glitch::EUnexpectedValue(
                    'Invalid dump yield key : '.$target
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
     */
    public function setType(string $type): Entity
    {
        $this->type = $type;
        $this->id = str_replace('.', '-', uniqid($type.'-', true));
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
     */
    public function setName(?string $name): Entity
    {
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
     */
    public function setOpen(bool $open): Entity
    {
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
     * Set object id
     */
    public function setId(?string $id): Entity
    {
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
     */
    public function setObjectId(?int $id): Entity
    {
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
     */
    public function setHash(?string $hash): Entity
    {
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
     */
    public function setClass(?string $class): Entity
    {
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
     */
    public function setClassName(?string $className): Entity
    {
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
     */
    public function setParentClasses(string ...$parents): Entity
    {
        if (empty($parents)) {
            $parents = null;
        }

        $this->parents = $parents;
        return $this;
    }

    /**
     * Get parent classes
     */
    public function getParentClasses(): ?array
    {
        return $this->parents;
    }


    /**
     * Set interfaces
     */
    public function setInterfaces(string ...$interfaces): Entity
    {
        if (empty($interfaces)) {
            $interfaces = null;
        }

        $this->interfaces = $interfaces;
        return $this;
    }

    /**
     * Get interfaces
     */
    public function getInterfaces(): ?array
    {
        return $this->interfaces;
    }


    /**
     * Set traits
     */
    public function setTraits(string ...$traits): Entity
    {
        if (empty($traits)) {
            $traits = null;
        }

        $this->traits = $traits;
        return $this;
    }

    /**
     * Get traits
     */
    public function getTraits(): ?array
    {
        return $this->traits;
    }



    /**
     * Set source file
     */
    public function setFile(?string $file): Entity
    {
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
     */
    public function setStartLine(?int $line): Entity
    {
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
     */
    public function setEndLine(?int $line): Entity
    {
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
     */
    public function setText(?string $text): Entity
    {
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
     */
    public function setDefinition(?string $definition): Entity
    {
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
     */
    public function setLength(?int $length): Entity
    {
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
     */
    public function setMeta(string $key, $value): Entity
    {
        $this->checkValidity($value);

        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * Get meta value
     */
    public function getMeta(string $key)
    {
        return $this->meta[$key] ?? null;
    }

    /**
     * Set meta list
     */
    public function setMetaList(array $meta): Entity
    {
        foreach ($meta as $key => $value) {
            $this->setMeta($key, $value);
        }

        return $this;
    }

    /**
     * Get all meta data
     */
    public function getMetaList(): ?array
    {
        return $this->meta;
    }

    /**
     * Has meta value
     */
    public function hasMeta(string $key): bool
    {
        if ($this->meta === null) {
            return false;
        }

        return array_key_exists($key, $this->meta);
    }

    /**
     * Remove meta value
     */
    public function removeMeta(string $key): Entity
    {
        unset($this->meta[$key]);
        return $this;
    }

    /**
     * Clear meta list
     */
    public function clearMeta(): Entity
    {
        $this->meta = [];
        return $this;
    }



    /**
     * Set value by key
     */
    public function setValue($key, $value): Entity
    {
        $this->checkValidity($value);

        $this->values[$key] = $value;
        return $this;
    }

    /**
     * Get value by key
     */
    public function getValue($key)
    {
        return $this->values[$key] ?? null;
    }

    /**
     * Set single value
     */
    public function setSingleValue($value): Entity
    {
        $this->setValues([$value]);
        $this->setShowKeys(false);
        return $this;
    }

    /**
     * Get single value
     */
    public function getSingleValue()
    {
        return $this->values[0] ?? null;
    }

    /**
     * Set values list
     */
    public function setValues(?array $values): Entity
    {
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
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * Has value
     */
    public function hasValue($key): bool
    {
        return isset($this->values[$key]);
    }

    /**
     * Remove value
     */
    public function removeValue($key): Entity
    {
        unset($this->values[$key]);
        return $this;
    }

    /**
     * Clear values
     */
    public function clearValues(): Entity
    {
        $this->values = [];
        return $this;
    }


    /**
     * Set show keys
     */
    public function setShowKeys(bool $show): Entity
    {
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
     */
    public function setProperties(array $properties): Entity
    {
        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }

        return $this;
    }

    /**
     * Get properties
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }


    /**
     * Set property
     */
    public function setProperty(string $key, $value): Entity
    {
        $this->checkValidity($value);
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Get property
     */
    public function getProperty(string $key)
    {
        return $this->properties[$key] ?? null;
    }

    /**
     * Has property
     */
    public function hasProperty(string $key): bool
    {
        if (empty($this->properties)) {
            return false;
        }

        return array_key_exists($key, $this->properties);
    }

    /**
     * Remove property
     */
    public function removeProperty(string $key): Entity
    {
        unset($this->properties[$key]);
        return $this;
    }

    /**
     * Remove all properties
     */
    public function clearProperties(): Entity
    {
        $this->properties = [];
        return $this;
    }



    /**
     * Set stack trace
     */
    public function setStackTrace(Trace $trace): Entity
    {
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
    protected function checkValidity($value, string $type=null): void
    {
        if ($type !== null) {
            if (!$this->checkTypeValidity($value, $type)) {
                throw \Glitch::EUnexpectedValue(
                    'Invalid dump yield value type ('.$type.')'
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
                throw \Glitch::EUnexpectedValue(
                    'Invalid sub-entity type - must be scalar or Entity',
                    null,
                    $value
                );
        }
    }

    /**
     * Check value type
     */
    protected function checkTypeValidity($value, string $type): bool
    {
        if ($nullable = (substr($type, 0, 1) === '?')) {
            if ($value === null) {
                return true;
            }

            $type = substr($type, 1);
        }

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
     */
    public function hideSection(string $name): Entity
    {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = false;
        }

        return $this;
    }

    /**
     * Show entity section
     */
    public function showSection(string $name): Entity
    {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = true;
        }

        return $this;
    }

    /**
     * Set entity section visible
     */
    public function setSectionVisible(string $name, bool $visible): Entity
    {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = $visible;
        }

        return $this;
    }

    /**
     * Is section visible
     */
    public function isSectionVisible(string $name): bool
    {
        return $this->sections[$name] ?? false;
    }

    /**
     * Set section visibility map
     */
    public function setSectionsVisible(array $sections): Entity
    {
        foreach ($sections as $section => $visible) {
            $this->setSectionVisible($section, (bool)$visible);
        }

        return $this;
    }

    /**
     * Get section visibility
     */
    public function getSectionVisibility(): array
    {
        return $this->sections;
    }
}
