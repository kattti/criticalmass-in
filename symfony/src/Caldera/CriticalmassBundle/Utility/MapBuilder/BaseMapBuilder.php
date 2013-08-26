<?php

namespace Caldera\CriticalmassBundle\Utility\MapBuilder;

use \Caldera\CriticalmassBundle\Utility\MapElement as MapElement;
use \Caldera\CriticalmassBundle\Entity as Entity;
use \Caldera\CriticalmassBundle\Utility\PositionArray as PositionArray;

abstract class BaseMapBuilder
{
	protected $positionArray;

	protected $mainPositions;

	protected $additionalPositions;

	protected $ride;

	public function __construct(Entity\Ride $ride, $positions)
	{
		$this->ride = $ride;

		$this->positionArray = new PositionArray($positions);
	}

	public abstract function getUserCounter();

	public abstract function getAverageSpeed();

	public abstract function getZoomFactor();

	public abstract function getMapCenterLatitude();

	public abstract function getMapCenterLongitude();

	public abstract function calculateMainPositions();

	public abstract function calculateAdditionalPositions();

	public function getMainPositions()
	{
		$resultArray = array();
		$counter = 0;

		foreach ($this->mainPositions->getPositions() as $position)
		{
			$circle = new MapElement\CircleMapElement($position, 100);

			$resultArray['position-'.$counter] = $circle->draw();
			++$counter;
		}

		return $resultArray;
	}

	public function getAdditionalPositions()
	{
		$resultArray = array();

		foreach ($this->additionalPositions->getPositions() as $position)
		{
			$circle = new MapElement\CircleMapElement($position, 10);

			$resultArray[$position->getId()] = $circle->draw();
		}

		return $resultArray;

	}

	public function draw()
	{
		$elements = array();

		$elements = array_merge($elements, $this->getMainPositions());
		
		$elements = array_merge($elements, $this->getAdditionalPositions());

		return array(
			'mapcenter' => array(
				'latitude' => $this->getMapCenterLatitude(),
				'longitude' => $this->getMapCenterLongitude()
				),
				'zoom' => $this->getZoomFactor(),
				'elements' => $elements,
				'usercounter' => $this->getUserCounter(),
				'averagespeed' => $this->getAverageSpeed()
			);
	}
}