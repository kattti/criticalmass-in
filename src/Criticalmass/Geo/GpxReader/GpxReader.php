<?php declare(strict_types=1);

namespace App\Criticalmass\Geo\GpxReader;

use App\Criticalmass\Geo\Entity\Position;
use App\Criticalmass\Geo\EntityInterface\PositionInterface;
use App\Criticalmass\Geo\Exception\GpxFileNotFoundException;
use App\Criticalmass\Geo\PositionList\PositionList;
use App\Criticalmass\Geo\PositionList\PositionListInterface;

class GpxReader implements GpxReaderInterface
{
    /** @var \SimpleXMLElement $rootNode */
    protected $rootNode;

    /** @var \SimpleXMLElement[]  $trackPointList */
    protected $trackPointList = [];

    public function loadFromString(string $gpxString): GpxReaderInterface
    {
        $this->prepareGpx($gpxString);

        return $this;
    }

    public function loadFromFile(string $filename): GpxReaderInterface
    {
        try {
            $gpxString = file_get_contents($filename);
        } catch (\Exception $exception) {
            throw new GpxFileNotFoundException($exception);
        }

        $this->prepareGpx($gpxString);

        return $this;
    }

    protected function prepareGpx(string $xmlString): GpxReader
    {
        $this->rootNode = new \SimpleXMLElement($xmlString);

        $this->registerXpathNamespace('gpx', 'http://www.topografix.com/GPX/1/1');

        $this->createTrackPointList();

        return $this;
    }

    protected function registerXpathNamespace(string $prefix, string $namespace): GpxReader
    {
        $this->rootNode->registerXPathNamespace($prefix, $namespace);

        return $this;
    }

    protected function createTrackPointList(): GpxReader
    {
        $this->trackPointList = $this->rootNode->xpath('//gpx:trkpt');

        return $this;
    }

    public function getRootNode(): \SimpleXMLElement
    {
        return $this->rootNode;
    }

    public function getCreationDateTime(): \DateTime
    {
        return new \DateTime((string) $this->rootNode->metadata->time);
    }

    public function getStartDateTime(): \DateTime
    {
        return new \DateTime((string) $this->trackPointList[0]->time);
    }

    public function getEndDateTime(): \DateTime
    {
        $lastTrackPointNumber = count($this->rootNode->trk->trkseg->trkpt) - 1;

        return new \DateTime((string) $this->trackPointList[$lastTrackPointNumber]->time);
    }

    public function countPoints(): int
    {
        return count($this->trackPointList);
    }

    public function getLatitudeOfPoint(int $n): float
    {
        return (float) $this->trackPointList[$n]['lat'];
    }

    public function getLongitudeOfPoint(int $n): float
    {
        return (float) $this->trackPointList[$n]['lon'];
    }

    public function getElevationOfPoint(int $n): float
    {
        return (float) $this->trackPointList[$n]->ele[0];
    }

    public function getDateTimeOfPoint(int $n): \DateTime
    {
        return new \DateTime((string) $this->trackPointList[$n]->time);
    }

    public function getPoint(int $n): \SimpleXMLElement
    {
        return $this->trackPointList[$n];
    }

    public function createPosition(int $n): PositionInterface
    {
        /** @var PositionInterface $position */
        $position = new Position(
            $this->getLatitudeOfPoint($n),
            $this->getLongitudeOfPoint($n)
        );

        $position
            ->setAltitude($this->getElevationOfPoint($n))
            ->setDateTime($this->getDateTimeOfPoint($n))
        ;

        return $position;
    }

    public function createPositionList(): PositionListInterface
    {
        $positionList = new PositionList();

        for ($n = 0; $n < $this->countPoints(); ++$n) {
            $position = $this->createPosition($n);

            $positionList->add($position);
        }

        return $positionList;
    }
}
