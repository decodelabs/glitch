<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;
use ReflectionObject;

class Date
{
    /**
     * Inspect DateTimeInterface
     */
    public static function inspectDateTime(
        DateTimeInterface $date,
        Entity $entity,
        Inspector $inspector
    ): void {
        $fromNow = (new DateTime())->diff($date);

        $entity
            ->setText($date->format('H:i:s jS M Y T'))
            ->setMeta('w3c', $inspector($date->format($date::W3C)))
            ->setMeta('timezone', $inspector($date->format('e')))
            ->setMeta('utc', $inspector($date->format('P')))
            ->setMeta('fromNow', $inspector(self::formatInterval($fromNow)))
        ;
    }


    /**
     * Inspect DateInterval
     */
    public static function inspectDateInterval(
        DateInterval $interval,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity
            ->setText(self::formatInterval($interval));

        $inspector->inspectClassMembers(
            $interval,
            new ReflectionObject($interval),
            $entity,
            [],
            true
        );
    }


    /**
     * Format DateInterval
     */
    protected static function formatInterval(
        DateInterval $interval,
        bool $nominal = true
    ): string {
        $format = '';

        if (
            $interval->y === 0 &&
            $interval->m === 0 &&
            (
                $interval->h >= 24 ||
                $interval->i >= 60 ||
                $interval->s >= 60
            )
        ) {
            $date1 = new DateTime();
            $date2 = clone $date1;

            /** @phpstan-ignore-next-line */
            if (false === $date2->add($interval)) {
                throw Exceptional::Runtime(
                    message: 'Unable to create date from interval'
                );
            }

            $interval = date_diff($date1, $date2);
            $format .= 0 < $interval->days ? '%ad ' : '';
        } else {
            if ($interval->y) {
                $format .= '%yy ';
            }

            if ($interval->m) {
                $format .= '%mm ';
            }

            if ($interval->d) {
                $format .= '%dd ';
            }
        }

        if ($interval->h || !empty($format)) {
            $format .= '%H:';
        }
        if ($interval->i || !empty($format)) {
            $format .= '%I:';
        }
        if ($interval->s || !empty($format)) {
            $format .= '%S';
        }

        $format = trim($format);

        if (empty($format)) {
            $format = '0s';
        }

        if ($nominal) {
            $format = '%R ' . $format;
        }

        return $interval->format($format);
    }

    /**
     * Inspect DateTimeZone
     */
    public static function inspectDateTimeZone(
        DateTimeZone $timezone,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity
            ->setText($timezone->getName());

        $inspector->inspectClassMembers(
            $timezone,
            new ReflectionObject($timezone),
            $entity,
            ['timezone'],
            true
        );
    }

    /**
     * Format seconds interval
     */
    protected static function formatSeconds(
        ?int $seconds,
        ?float $micro
    ): string {
        $t = rtrim((string)$micro, '0');
        $len = strlen($t);

        if ($len === 0) {
            $f = '0';
        } elseif ($len <= 3) {
            $f = str_pad($t, 3, '0');
        } else {
            $f = $micro;
        }

        return sprintf('%02d.%s', $seconds, $f);
    }



    /**
     * Inspect DatePeriod
     *
     * @param DatePeriod<DateTimeInterface,?DateTimeInterface,int> $period
     */
    public static function inspectDatePeriod(
        DatePeriod $period,
        Entity $entity,
        Inspector $inspector
    ): void {
        $entity
            ->setText(sprintf(
                'every %s, from %s%s%s',
                self::formatInterval($period->getDateInterval(), false),
                $period->getStartDate()->format('Y-m-d H:i:s'),
                $period->include_start_date ? ' inc' : '',
                null !== ($end = $period->getEndDate()) ?
                    ' to ' . $end->format('Y-m-d H:i:s') :
                    ', ' . $period->recurrences . ' time(s)'
            ));

        $inspector->inspectClassMembers(
            $period,
            new ReflectionObject($period),
            $entity,
            [],
            true
        );
    }
}
