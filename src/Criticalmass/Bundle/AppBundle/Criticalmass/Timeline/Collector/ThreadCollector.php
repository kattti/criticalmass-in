<?php

namespace Criticalmass\Bundle\AppBundle\Criticalmass\Timeline\Collector;

use Criticalmass\Bundle\AppBundle\Entity\Thread;
use Criticalmass\Bundle\AppBundle\Criticalmass\Timeline\Item\ThreadItem;

class ThreadCollector extends AbstractTimelineCollector
{
    protected $entityClass = Thread::class;

    protected function convertGroupedEntities(array $groupedEntities): AbstractTimelineCollector
    {
        /** @var Thread $threadEntity */
        foreach ($groupedEntities as $threadEntity) {
            $item = new ThreadItem();

            $item->setUsername($threadEntity->getFirstPost()->getUser()->getUsername());
            $item->setThread($threadEntity);
            $item->setTitle($threadEntity->getTitle());
            $item->setText($threadEntity->getFirstPost()->getMessage());
            $item->setDateTime($threadEntity->getFirstPost()->getDateTime());

            $this->addItem($item);
        }

        return $this;
    }
}
