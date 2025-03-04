<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DecodeLabs\Coercion;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;
use PDO as PDOAdapter;
use PDOStatement;
use Throwable;

class Pdo
{
    protected const Attributes = [
        'AUTOCOMMIT',
        'PREFETCH',
        'TIMEOUT',
        'ERRMODE',
        'SERVER_VERSION',
        'CLIENT_VERSION',
        'SERVER_INFO',
        'CONNECTION_STATUS',
        'CASE',
        'CURSOR_NAME',
        'CURSOR',
        'DRIVER_NAME',
        'ORACLE_NULLS',
        'PERSISTENT',
        'STATEMENT_CLASS',
        'FETCH_CATALOG_NAMES',
        'FETCH_TABLE_NAMES',
        'STRINGIFY_FETCHES',
        'MAX_COLUMN_LEN',
        'DEFAULT_FETCH_MODE',
        'EMULATE_PREPARES',
        'DEFAULT_STR_PARAM'
    ];

    /**
     * Inspect PDO connection
     */
    public static function inspectPdo(
        PDOAdapter $pdo,
        Entity $entity,
        Inspector $inspector
    ): void {
        foreach (self::Attributes as $name) {
            try {
                $entity->setMeta(
                    $name,
                    $inspector(
                        $pdo->getAttribute(
                            Coercion::asInt(constant('PDO::ATTR_' . $name))
                        )
                    )
                );
            } catch (Throwable $e) {
            }
        }

        $entity
            ->setMeta('availableDrivers', $inspector($pdo->getAvailableDrivers()))
            ->setProperty('*inTransaction', $inspector($pdo->inTransaction()))
            ->setProperty('*lastInsertId', $inspector($pdo->lastInsertId()));
    }

    /**
     * Inspect PDO statement
     */
    public static function inspectPdoStatement(
        PDOStatement $statement,
        Entity $entity,
        Inspector $inspector
    ): void {
        ob_start();
        $statement->debugDumpParams();

        if (false === ($dump = ob_get_clean())) {
            $dump = null;
        }

        $entity
            ->setText($dump)
            ->setSectionVisible('text', false)
            ->setDefinition($statement->queryString)
            ->setLength($statement->columnCount());
    }
}
