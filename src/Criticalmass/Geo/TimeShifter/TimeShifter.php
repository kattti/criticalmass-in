<?php declare(strict_types=1);

namespace App\Criticalmass\Geo\TimeShifter;

class TimeShifter extends AbstractTimeShifter
{
    public function shift(\DateInterval $interval): TimeShifterInterface
    {
        for ($i = 0; $i < count($this->positionList); ++$i) {
            $position = $this->positionList->get($i);

            $dateTime = new \DateTime(sprintf('@%d', $position->getTimestamp()));
            $dateTime->add($interval);
            $position->setTimestamp($dateTime->getTimestamp());
        }

        return $this;
    }
}
