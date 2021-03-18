<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Dumper\Inspect;

use ArrayIterator;
use ArrayObject;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

use SplDoublyLinkedList;
use SplFileInfo;
use SplFileObject;
use SplFixedArray;
use SplHeap;
use SplObjectStorage;
use SplPriorityQueue;

class Spl
{
    /**
     * Inspect array object
     */
    public static function inspectArrayObject(ArrayObject $arr, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('!flags', $inspector->inspectFlagSet($arr->getFlags(), [
                'ArrayObject::STD_PROP_LIST',
                'ArrayObject::ARRAY_AS_PROPS'
            ]))
            ->setValues($inspector->inspectList($arr->getArrayCopy()));
    }

    /**
     * Inspect array iterator
     */
    public static function inspectArrayIterator(ArrayIterator $arr, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('!flags', $inspector->inspectFlagSet($arr->getFlags(), [
                'ArrayIterator::STD_PROP_LIST',
                'ArrayIterator::ARRAY_AS_PROPS'
            ]))
            ->setValues($inspector->inspectList($arr->getArrayCopy()));
    }

    /**
     * Inspect SPL Doubly Linked List
     */
    public static function inspectSplDoublyLinkedList(SplDoublyLinkedList $list, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('!iteratorMode', $inspector->inspectFlagSet($list->getIteratorMode(), [
                'SplDoublyLinkedList::IT_MODE_LIFO',
                'SplDoublyLinkedList::IT_MODE_FIFO',
                'SplDoublyLinkedList::IT_MODE_DELETE',
                'SplDoublyLinkedList::IT_MODE_KEEP'
            ]))
            ->setLength(count($list))
            ->setValues($inspector->inspectList(iterator_to_array(clone $list)));
    }

    /**
     * Inspect SPL Heap
     */
    public static function inspectSplHeap(SplHeap $heap, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setLength(count($heap))
            ->setValues($inspector->inspectList(iterator_to_array(clone $heap)));
    }


    /**
     * Inspect SPL PriorityQueue
     */
    public static function inspectSplPriorityQueue(SplPriorityQueue $queue, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setLength($queue->count())
            ->setProperty('*extractFlags', $inspector->inspectFlagSet($queue->getExtractFlags(), [
                'SplPriorityQueue::EXTR_DATA',
                'SplPriorityQueue::EXTR_PRIORITY',
                'SplPriorityQueue::EXTR_BOTH'
            ]))
            ->setValues($inspector->inspectList(iterator_to_array(clone $queue)));
    }


    /**
     * Inspect SPL Fixed Array
     */
    public static function inspectSplFixedArray(SplFixedArray $arr, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setLength($arr->getSize())
            ->setValues($inspector->inspectList($arr->toArray()));
    }

    /**
     * Inspect SPL ObjectStorage
     */
    public static function inspectSplObjectStorage(SplObjectStorage $store, Entity $entity, Inspector $inspector): void
    {
        $values = [];

        foreach (clone $store as $object) {
            $values[] = [
                'object' => $object,
                'info' => $store->getInfo()
            ];
        }

        $entity
            ->setLength($store->count())
            ->setValues($inspector->inspectList($values))
            ->setShowKeys(false);
    }




    /**
     * Inspect SPL FileInfo
     */
    public static function inspectSplFileInfo(SplFileInfo $file, Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setText(Glitch::normalizePath($file->getPathname()))
            ->setMeta('type', $inspector($type = $file->getType()));

        if ($type == 'link') {
            $entity->setMeta('target', $inspector($file->getLinkTarget()));
        }

        $entity
            ->setMeta('size', Context::formatFilesize($file->getSize()))
            ->setMeta('perms', $inspector(decoct($file->getPerms())))
            ->setMeta('aTime', $inspector(date('Y-m-d H:i:s', $file->getATime())))
            ->setMeta('mTime', $inspector(date('Y-m-d H:i:s', $file->getMTime())))
            ->setMeta('cTime', $inspector(date('Y-m-d H:i:s', $file->getCTime())));
    }

    /**
     * Inspect SPL FileObject
     */
    public static function inspectSplFileObject(SplFileObject $file, Entity $entity, Inspector $inspector): void
    {
        self::inspectSplFileInfo($file, $entity, $inspector);

        $entity
            ->setMeta('eof', $inspector($file->eof()))
            ->setMeta('key', $inspector($file->key()))
            ->setMeta('flags', $inspector->inspectFlagSet($file->getFlags(), [
                'SplFileObject::DROP_NEW_LINE',
                'SplFileObject::READ_AHEAD',
                'SplFileObject::SKIP_EMPTY',
                'SplFileObject::READ_CSV',
            ]));
    }
}
