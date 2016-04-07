<?php

namespace Caldera\Bundle\CriticalmassCoreBundle\Timeline\Collector;

use Caldera\Bundle\CriticalmassCoreBundle\Timeline\Item\ItemInterface;

abstract class AbstractTimelineCollector
{
    protected $doctrine;

    protected $items = [];

    public function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    abstract public function execute();

    public function getItems()
    {
        return $this->items;
    }

    protected function addItem(ItemInterface $item)
    {
        $dateTimeString = $item->getDateTime()->format('Y-m-d-H-i-s');

        $itemKey = $dateTimeString.'-'.$item->getUniqId();

        $this->items[$itemKey] = $item;
    }
}