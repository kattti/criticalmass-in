<?php

namespace AppBundle\Timeline\Collector;

use AppBundle\Entity\Post;
use AppBundle\Timeline\Item\PhotoCommentItem;

class PhotoCommentCollector extends AbstractTimelineCollector
{
    protected function fetchEntities()
    {
        return $this->doctrine->getRepository('AppBundle:Post')->findForTimelinePhotoCommentCollector($this->startDateTime, $this->endDateTime);
    }

    protected function groupEntities(array $entities)
    {
        return $entities;
    }

    protected function convertGroupedEntities(array $groupedEntities)
    {
        /**
         * @var Post $threadEntity
         */
        foreach ($groupedEntities as $postEntity) {
            $item = new PhotoCommentItem();

            $item->setUsername($postEntity->getUser()->getUsername());
            $item->setRideTitle($postEntity->getPhoto()->getRide()->getFancyTitle());
            $item->setPhoto($postEntity->getPhoto());
            $item->setText($postEntity->getMessage());
            $item->setDateTime($postEntity->getDateTime());

            $this->addItem($item);
        }
    }
}