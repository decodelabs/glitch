<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;
use DecodeLabs\Glitch\Stack\Trace;

use df\core\IDumpable as R7Dumpable;
use df\core\debug\dumper\Property as R7Property;

class R7
{
    /**
     * Inspect generic exception
     */
    public static function inspectDumpable(R7Dumpable $object, Entity $entity, Inspector $inspector): void
    {
        $data = $object->getDumpProperties();

        if (is_string($data)) {
            $entity->setText($data);
        } elseif (is_array($data)) {
            $values = [];

            foreach ($data as $key => $value) {
                if ($value instanceof R7Property) {
                    switch ($value->getVisibility()) {
                        case 'protected':
                            $prefix = '*';
                            break;

                        case 'private':
                            $prefix = '!';
                            break;

                        default:
                            $prefix = '';
                            break;
                    }

                    $entity->setProperty($key, $inspector($value->getValue()));
                } else {
                    $values[$key] = $inspector($value);
                }
            }

            if (!empty($values)) {
                $entity->setValues($values);
            }
        } else {
            $entity
                ->setValues([$inspector($data)])
                ->setShowKeys(false);
        }
    }
}
