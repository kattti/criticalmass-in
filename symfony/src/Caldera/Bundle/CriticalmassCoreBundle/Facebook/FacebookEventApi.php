<?php

namespace Caldera\Bundle\CriticalmassCoreBundle\Facebook;

use Caldera\Bundle\CriticalmassCoreBundle\Utils\DateTimeUtils;
use Caldera\Bundle\CriticalmassModelBundle\Entity\City;
use Caldera\Bundle\CriticalmassModelBundle\Entity\Ride;
use Facebook\GraphNodes\GraphEdge;
use Facebook\GraphNodes\GraphEvent;

class FacebookEventApi extends FacebookApi
{
    public function getEventForCityMonth(City $city, \DateTime $month)
    {
        $pageId = $this->getCityPageId($city);
        $since = DateTimeUtils::getMonthStartDateTime($month)->format('U');
        $until = DateTimeUtils::getMonthEndDateTime($month)->format('U');

        return $this->queryEvents($pageId, $since, $until);
    }

    public function getEventForRide(Ride $ride)
    {
        $pageId = $this->getCityPageId($ride->getCity());
        $since = DateTimeUtils::getMonthStartDateTime($ride->getDateTime())->format('U');
        $until = DateTimeUtils::getMonthEndDateTime($ride->getDateTime())->format('U');

        return $this->queryEvents($pageId, $since, $until);
    }

    protected function queryEvents($pageId, $since, $until)
    {
        try {
            $response = $this->facebook->get('/' . $pageId . '/events?since=' . $since . '&until=' . $until);
        } catch (\Exception $e) {
            return null;
        }

        try {
            /**
             * @var GraphEdge $eventEdge
             */
            $eventEdge = $response->getGraphEdge('GraphEvent');
        } catch (\Exception $e) {
            return null;
        }

        /**
         * @var GraphEvent $event
         */
        $event = null;

        foreach ($eventEdge as $event) {
        }

        return $event;
    }

    protected function queryEvent($eventId, array $fields = [])
    {
        $fieldString = implode(',', $fields);

        try {
            $response = $this->facebook->get('/'.$eventId.'?fields='.$fieldString);
        } catch (\Exception $e) {
            return null;
        }

        try {
            /**
             * @var GraphEvent $event
             */
            $event = $response->getGraphEvent();
        } catch (\Exception $e) {
            return null;
        }

        return $event;
    }
}